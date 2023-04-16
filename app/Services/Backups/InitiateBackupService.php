<?php

namespace Kubectyl\Services\Backups;

use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Kubectyl\Models\Snapshot;
use Kubectyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Kubectyl\Extensions\Backups\BackupManager;
use Kubectyl\Repositories\Eloquent\BackupRepository;
use Kubectyl\Repositories\Kuber\DaemonBackupRepository;
use Kubectyl\Exceptions\Service\Backup\TooManyBackupsException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class InitiateBackupService
{
    private ?array $ignoredFiles;

    private bool $isLocked = false;

    /**
     * InitiateBackupService constructor.
     */
    public function __construct(
        private BackupRepository $repository,
        private ConnectionInterface $connection,
        private DaemonBackupRepository $daemonBackupRepository,
        private DeleteBackupService $deleteBackupService,
        private BackupManager $backupManager
    ) {
    }

    /**
     * Set if the snapshot should be locked once it is created which will prevent
     * its deletion by users or automated system processes.
     */
    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    /**
     * Sets the files to be ignored by this snapshot.
     *
     * @param string[]|null $ignored
     */
    public function setIgnoredFiles(?array $ignored): self
    {
        if (is_array($ignored)) {
            foreach ($ignored as $value) {
                Assert::string($value);
            }
        }

        // Set the ignored files to be any values that are not empty in the array. Don't use
        // the PHP empty function here incase anything that is "empty" by default (0, false, etc.)
        // were passed as a file or folder name.
        $this->ignoredFiles = is_null($ignored) ? [] : array_filter($ignored, function ($value) {
            return strlen($value) > 0;
        });

        return $this;
    }

    /**
     * Initiates the snapshot process for a server on Wings.
     *
     * @throws \Throwable
     * @throws \Kubectyl\Exceptions\Service\Backup\TooManyBackupsException
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function handle(Server $server, string $name = null, bool $override = false): Snapshot
    {
        $limit = config('snapshots.throttles.limit');
        $period = config('snapshots.throttles.period');
        if ($period > 0) {
            $previous = $this->repository->getBackupsGeneratedDuringTimespan($server->id, $period);
            if ($previous->count() >= $limit) {
                $message = sprintf('Only %d snapshots may be generated within a %d second span of time.', $limit, $period);

                throw new TooManyRequestsHttpException(CarbonImmutable::now()->diffInSeconds($previous->last()->created_at->addSeconds($period)), $message);
            }
        }

        // Check if the server has reached or exceeded its snapshot limit.
        // completed_at == null will cover any ongoing snapshots, while is_successful == true will cover any completed snapshots.
        $successful = $this->repository->getNonFailedBackups($server);
        if (!$server->snapshot_limit || $successful->count() >= $server->snapshot_limit) {
            // Do not allow the user to continue if this server is already at its limit and can't override.
            if (!$override || $server->snapshot_limit <= 0) {
                throw new TooManyBackupsException($server->snapshot_limit);
            }

            // Get the oldest snapshot the server has that is not "locked" (indicating a snapshot that should
            // never be automatically purged). If we find a snapshot we will delete it and then continue with
            // this process. If no snapshot is found that can be used an exception is thrown.
            /** @var \Kubectyl\Models\Snapshot $oldest */
            $oldest = $successful->where('is_locked', false)->orderBy('created_at')->first();
            if (!$oldest) {
                throw new TooManyBackupsException($server->snapshot_limit);
            }

            $this->deleteBackupService->handle($oldest);
        }

        return $this->connection->transaction(function () use ($server, $name) {
            /** @var \Kubectyl\Models\Snapshot $snapshot */
            $snapshot = $this->repository->create([
                'server_id' => $server->id,
                'uuid' => Uuid::uuid4()->toString(),
                'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => array_values($this->ignoredFiles ?? []),
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $this->isLocked,
            ], true, true);

            $this->daemonBackupRepository->setServer($server)
                ->setBackupAdapter($this->backupManager->getDefaultAdapter())
                ->snapshot($snapshot);

            return $snapshot;
        });
    }
}
