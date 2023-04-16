<?php

namespace Kubectyl\Tests\Unit\Http\Middleware\Api\Daemon;

use Mockery as m;
use Mockery\MockInterface;
use Kubectyl\Models\Cluster;
use Illuminate\Contracts\Encryption\Encrypter;
use Kubectyl\Repositories\Eloquent\ClusterRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Kubectyl\Exceptions\Repository\RecordNotFoundException;
use Kubectyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Kubectyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticateTest extends MiddlewareTestCase
{
    private MockInterface $encrypter;

    private MockInterface $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(ClusterRepository::class);
    }

    /**
     * Test that if we are accessing the daemon.configuration route this middleware is not
     * applied in order to allow an unauthenticated request to use a token to grab data.
     */
    public function testResponseShouldContinueIfRouteIsExempted()
    {
        $this->request->expects('route->getName')->withNoArgs()->andReturn('daemon.configuration');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that not passing in the bearer token will result in a HTTP/401 error with the
     * proper response headers.
     */
    public function testResponseShouldFailIfNoTokenIsProvided()
    {
        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturnNull();

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(401, $exception->getStatusCode(), 'Assert that a status code of 401 is returned.');
            $this->assertTrue(is_array($exception->getHeaders()), 'Assert that an array of headers is returned.');
            $this->assertArrayHasKey('WWW-Authenticate', $exception->getHeaders(), 'Assert exception headers contains WWW-Authenticate.');
            $this->assertEquals('Bearer', $exception->getHeaders()['WWW-Authenticate']);
        }
    }

    /**
     * Test that passing in an invalid cluster daemon secret will result in a bad request
     * exception being returned.
     *
     * @dataProvider badTokenDataProvider
     */
    public function testResponseShouldFailIfTokenFormatIsIncorrect(string $token)
    {
        $this->expectException(BadRequestHttpException::class);

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($token);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an access denied error is returned if the cluster is valid but the token
     * provided is not valid.
     */
    public function testResponseShouldFailIfTokenIsNotValid()
    {
        $this->expectException(AccessDeniedHttpException::class);

        /** @var \Kubectyl\Models\Cluster $model */
        $model = Cluster::factory()->make();

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($model->daemon_token_id . '.random_string_123');

        $this->repository->expects('findFirstWhere')->with(['daemon_token_id' => $model->daemon_token_id])->andReturn($model);
        $this->encrypter->expects('decrypt')->with($model->daemon_token)->andReturns(decrypt($model->daemon_token));

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an access denied exception is returned if the cluster is not found using
     * the token ID provided.
     */
    public function testResponseShouldFailIfClusterIsNotFound()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn('abcd1234.random_string_123');

        $this->repository->expects('findFirstWhere')->with(['daemon_token_id' => 'abcd1234'])->andThrow(RecordNotFoundException::class);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test a successful middleware process.
     */
    public function testSuccessfulMiddlewareProcess()
    {
        /** @var \Kubectyl\Models\Cluster $model */
        $model = Cluster::factory()->make();

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($model->daemon_token_id . '.' . decrypt($model->daemon_token));

        $this->repository->expects('findFirstWhere')->with(['daemon_token_id' => $model->daemon_token_id])->andReturn($model);
        $this->encrypter->expects('decrypt')->with($model->daemon_token)->andReturns(decrypt($model->daemon_token));

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('cluster');
        $this->assertRequestAttributeEquals($model, 'cluster');
    }

    /**
     * Provides different tokens that should trigger a bad request exception due to
     * their formatting.
     *
     * @return array|\string[][]
     */
    public function badTokenDataProvider(): array
    {
        return [
            ['foo'],
            ['foobar'],
            ['foo-bar'],
            ['foo.bar.baz'],
            ['.foo'],
            ['foo.'],
            ['foo..bar'],
        ];
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->encrypter, $this->repository);
    }
}
