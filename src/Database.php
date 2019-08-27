<?php

class Database
{
    const PRIVILEGES = ['ALTER', 'CREATE', 'DELETE', 'DROP', 'INDEX', 'INSERT', 'SELECT', 'TRIGGER', 'UPDATE'];

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
        $query = $this->pdo->query('SELECT * FROM INFORMATION_SCHEMA.USER_PRIVILEGES WHERE IS_GRANTABLE = "YES"');
        $tables = $query->fetchAll(PDO::FETCH_COLUMN, 2); // PRIVILEGE_TYPE
        $tables = array_unique($tables);
        $missed = [];
        foreach (static::PRIVILEGES as $k => $op) {
            if (!in_array($op, $tables)) {
                $missed[] = $op;
            }
        }
        if (!empty($missed)) {
            throw new Exception(strtr('Database user `%user%` don\'t have %privilege% privilege on the `%dbName%` database.', [
                '%user%' => $this->user,
                '%privilege%' => implode(', ', $missed),
                '%dbName%' => $this->name,
            ]));
        }
    }
}
