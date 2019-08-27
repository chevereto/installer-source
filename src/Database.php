<?php

class Database
{
    /** @var string */
    protected $host;

    /** @var string */
    protected $port;

    /** @var string */
    protected $name;

    /** @var string */
    protected $user;

    /** @var string */
    protected $userPassword;

    /** @var PDO */
    private $pdo;

    public function __construct(string $host, string $port, string $name, string $user, string $userPassword)
    {
        $pdoAttrs = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $userPassword, $pdoAttrs);
        $this->host = $host;
        $this->port = $port;
        $this->name = $name;
        $this->user = $user;
        $this->userPassword = $userPassword;
    }

    public function checkEmpty()
    {
        $query = $this->pdo->query("SHOW TABLES FROM `$this->name`;");
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tables)) {
            throw new Exception(sprintf('Database "%s" is not empty. Use another database or DROP (remove) all the tables in the target database.', $this->name));
        }
    }

    public function checkPrivileges()
    {
        $query = $this->pdo->query('SHOW GRANTS;');
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);
        $gotAllPrivileges = false;
        foreach ($tables as $v) {
            if (strpos(strtoupper($v), 'GRANT ALL PRIVILEGES') !== false) {
                $gotAllPrivileges = true;
                break;
            }
        }
        if (!$gotAllPrivileges) {
            throw new Exception(strtr('Database user "%user%" don\'t have ALL PRIVILEGES on the "%dbName%" database.', [
                '%user%' => $this->user,
                '%dbName%' => $this->name,
            ]));
        }
    }
}
