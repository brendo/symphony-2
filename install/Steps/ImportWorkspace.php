<?php
    namespace SymphonyCms\Installer\Steps;

    use \Psr\Log\LoggerInterface;
    use Configuration;
    use Symphony;
    use DatabaseException;
    use Exception;

    class ImportWorkspace implements Step
    {
        /**
         * @var LoggerInterface
         */
        protected $logger;

        /**
         * CreateManifest constructor.
         *
         * @param LoggerInterface $logger
         */
        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        /**
         * {@inheritdoc}
         */
        public function handle(Configuration $config, array $data)
        {
            $this->logger->info('An existing ‘workspace’ directory was found at this location. Symphony will use this workspace.');

            // MySQL: Importing workspace data
            $this->logger->info('MYSQL: Importing Workspace Data...');

            if (is_file(WORKSPACE . '/install.sql')) {
                try {
                    Symphony::Database()->import(file_get_contents(WORKSPACE . '/install.sql'));
                } catch (DatabaseException $e) {
                    throw new Exception(sprintf(
                        'There was an error while trying to import data to the database. MySQL returned: %s:%s',
                        $e->getDatabaseErrorCode(),
                        $e->getDatabaseErrorMessage()
                    ));
                }
            }

            return true;
        }
    }
