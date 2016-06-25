<?php
    namespace SymphonyCms\Installer\Steps;

    use \Psr\Log\LoggerInterface;
    use Configuration;
    use DatabaseException;
    use Symphony;
    use Author;
    use Cryptography;
    use Exception;

    class CreateDatabase implements Step
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
            // MySQL: Establishing connection
            $this->logger->info('MYSQL: Establishing Connection');

            try {
                Symphony::Database()->connect(
                    $config->get('host', 'database'),
                    $config->get('user', 'database'),
                    $config->get('password', 'database'),
                    $config->get('port', 'database'),
                    $config->get('db', 'database')
                );
            } catch (DatabaseException $e) {
                throw new Exception(
                    'There was a problem while trying to establish a connection to the MySQL server. Please check your settings.'
                );
            }

            // MySQL: Setting prefix & character encoding
            Symphony::Database()->setPrefix($config->get('tbl_prefix', 'database'));

            // MySQL: Importing schema
            $this->logger->info('MYSQL: Importing Table Schema');

            try {
                Symphony::Database()->import(file_get_contents(INSTALL . '/includes/install.sql'));
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying to import data to the database. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }

            // MySQL: Creating default author
            $this->logger->info('MYSQL: Creating Default Author');

            try {
                // Clean all the user data.
                $userData = array_map([Symphony::Database(), 'cleanValue'], $data['user']);

                $author = new Author;
                $author->set('user_type', 'developer');
                $author->set('primary', 'yes');
                $author->set('username', $userData['username']);
                $author->set('password', Cryptography::hash($userData['password']));
                $author->set('first_name', $userData['firstname']);
                $author->set('last_name', $userData['lastname']);
                $author->set('email', $userData['email']);
                $author->commit();
            } catch (DatabaseException $e) {
                throw new Exception(sprintf(
                    'There was an error while trying create the default author. MySQL returned: %s:%s',
                    $e->getDatabaseErrorCode(),
                    $e->getDatabaseErrorMessage()
                ));
            }

            return true;
        }
    }
