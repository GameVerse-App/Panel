<?php

namespace Kubectyl\Tests\Integration\Services\Databases;

use Mockery\MockInterface;
use Kubectyl\Models\Cluster;
use Kubectyl\Models\Database;
use Kubectyl\Models\DatabaseHost;
use Kubectyl\Tests\Integration\IntegrationTestCase;
use Kubectyl\Services\Databases\DatabaseManagementService;
use Kubectyl\Services\Databases\DeployServerDatabaseService;
use Kubectyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;

class DeployServerDatabaseServiceTest extends IntegrationTestCase
{
    private MockInterface $managementService;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->managementService = \Mockery::mock(DatabaseManagementService::class);
        $this->swap(DatabaseManagementService::class, $this->managementService);
    }

    /**
     * Ensure we reset the config to the expected value.
     */
    protected function tearDown(): void
    {
        config()->set('kubectyl.client_features.databases.allow_random', true);

        Database::query()->delete();
        DatabaseHost::query()->delete();

        parent::tearDown();
    }

    /**
     * Test that an error is thrown if either the database name or the remote host are empty.
     *
     * @dataProvider invalidDataProvider
     */
    public function testErrorIsThrownIfDatabaseNameIsEmpty(array $data)
    {
        $server = $this->createServerModel();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Expected a non-empty value\. Got: /');
        $this->getService()->handle($server, $data);
    }

    /**
     * Test that an error is thrown if there are no database hosts on the same cluster as the
     * server and the allow_random config value is false.
     */
    public function testErrorIsThrownIfNoDatabaseHostsExistOnCluster()
    {
        $server = $this->createServerModel();

        $cluster = Cluster::factory()->create(['location_id' => $server->location->id]);
        DatabaseHost::factory()->create(['cluster_id' => $cluster->id]);

        config()->set('kubectyl.client_features.databases.allow_random', false);

        $this->expectException(NoSuitableDatabaseHostException::class);

        $this->getService()->handle($server, [
            'database' => 'something',
            'remote' => '%',
        ]);
    }

    /**
     * Test that an error is thrown if no database hosts exist at all on the system.
     */
    public function testErrorIsThrownIfNoDatabaseHostsExistOnSystem()
    {
        $server = $this->createServerModel();

        $this->expectException(NoSuitableDatabaseHostException::class);

        $this->getService()->handle($server, [
            'database' => 'something',
            'remote' => '%',
        ]);
    }

    /**
     * Test that a database host on the same cluster as the server is preferred.
     */
    public function testDatabaseHostOnSameClusterIsPreferred()
    {
        $server = $this->createServerModel();

        $cluster = Cluster::factory()->create(['location_id' => $server->location->id]);
        DatabaseHost::factory()->create(['cluster_id' => $cluster->id]);
        $host = DatabaseHost::factory()->create(['cluster_id' => $server->cluster_id]);

        $this->managementService->expects('create')->with($server, [
            'database_host_id' => $host->id,
            'database' => "s{$server->id}_something",
            'remote' => '%',
        ])->andReturns(new Database());

        $response = $this->getService()->handle($server, [
            'database' => 'something',
            'remote' => '%',
        ]);

        $this->assertInstanceOf(Database::class, $response);
    }

    /**
     * Test that a database host not assigned to the same cluster as the server is used if
     * there are no same-cluster hosts and the allow_random configuration value is set to
     * true.
     */
    public function testDatabaseHostIsSelectedIfNoSuitableHostExistsOnSameCluster()
    {
        $server = $this->createServerModel();

        $cluster = Cluster::factory()->create(['location_id' => $server->location->id]);
        $host = DatabaseHost::factory()->create(['cluster_id' => $cluster->id]);

        $this->managementService->expects('create')->with($server, [
            'database_host_id' => $host->id,
            'database' => "s{$server->id}_something",
            'remote' => '%',
        ])->andReturns(new Database());

        $response = $this->getService()->handle($server, [
            'database' => 'something',
            'remote' => '%',
        ]);

        $this->assertInstanceOf(Database::class, $response);
    }

    public function invalidDataProvider(): array
    {
        return [
            [['remote' => '%']],
            [['database' => null, 'remote' => '%']],
            [['database' => '', 'remote' => '%']],
            [['database' => '']],
            [['database' => '', 'remote' => '']],
        ];
    }

    private function getService(): DeployServerDatabaseService
    {
        return $this->app->make(DeployServerDatabaseService::class);
    }
}
