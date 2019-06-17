<?php
/**
 * A PHP client for cPanel UAPI.
 */
class Cpanel
{
    /** @var string cPanel UAPI module/function */
    public $action;

    /** @var array */
    public $response;

    /** @var string */
    public $errorMessage;

    /** @var string user:password */
    protected $userpwd;

    /** @var string */
    protected $mysqlprefix;

    /** @var int */
    protected $mysqlMaxDbNamelength;

    /** @var int */
    protected $mysqlMaxUsernameLength;

    public function __construct(string $user, string $password)
    {
        $this->userpwd = "$user:$password";
    }

    public function sendRequest(string $action, array $params = [])
    {
        $endpoint = 'https://127.0.0.1:2083/execute/'.$action;
        $url = $endpoint.'?'.http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->userpwd);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        if ($result == false) {
            throw new Exception('curl_exec threw error "'.curl_error($ch)."\" for $action");
        }
        curl_close($ch);
        $array = json_decode($result, false);
        if (!$array) {
            throw new Exception("Can't authenticate in cPanel host (wrong user:password?)", 'login');
        }
        $this->action = $action;
        $this->response = $array;
        $this->isSuccess = 1 == $array->status;
        if (!$this->isSuccess) {
            $this->errorMessage = implode('-', $this->response->errors);
        }
    }

    /**
     * Creates a MySQL database, its user and set privileges.
     *
     * @param string $prefix vendor prefix
     *
     * @return array ['db_name' =>, 'db_user' =>, 'db_user_password' =>,]
     */
    public function setupMysql(string $prefix = null)
    {
        $this->sendRequest('Mysql/get_restrictions');
        // ^^^ response->data:
        // prefix => chevereto_
        // max_database_name_length => 64
        // max_username_length => 47

        if (!$this->isSuccess) {
            throw new Exception($this->errorMessage);
        }

        $this->mysqlprefix = $this->response->data->prefix;
        $this->mysqlMaxDbNamelength = $this->response->data->max_database_name_length;
        $this->mysqlMaxUsernameLength = $this->response->data->max_username_length;

        $dbPrefix = $this->mysqlprefix.$prefix;

        for ($i = 0; $i < 5; ++$i) {
            $dbName = static::getDbRandomName($dbPrefix, $this->mysqlMaxDbNamelength);
            $this->sendRequest('Mysql/check_database', ['name' => $dbName]);
            if (!$this->isSuccess) { // No DB = profit
                break;
            } else {
                if ($i == 4) {
                    throw new Exception('Unable to determine a valid MySQL database name', 201);
                }
            }
        }

        $this->sendRequest('Mysql/create_database', ['name' => $dbName]);
        if (!$this->isSuccess) {
            throw new Exception($this->errorMessage);
        }

        $dbUserPassword = password(16);
        for ($i = 0; $i < 5; ++$i) {
            $dbUser = static::getDbRandomName($dbPrefix, $this->mysqlMaxUsernameLength);
            $this->sendRequest('Mysql/create_user', [
                'name' => $dbUser,
                'password' => $dbUserPassword,
            ]);
            if ($this->isSuccess) {
                break;
            } else {
                if ($i == 4) {
                    throw new Exception('Unable to create the MySQL database user', 202);
                }
            }
        }

        $this->sendRequest('Mysql/set_privileges_on_database', [
            'user' => $dbUser,
            'database' => $dbName,
            'privileges' => 'ALL PRIVILEGES',
        ]);
        if (!$this->isSuccess) {
            throw new Exception($this->errorMessage);
        }

        return [
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_user_password' => $dbUserPassword,
        ];
    }

    public static function getDbRandomName(string $prefix, int $maxLength)
    {
        $maxRandomLength = $maxLength - strlen($prefix);
        if ($maxRandomLength <= 0) {
            return $prefix;
        }
        $randomLength = min(5, $maxRandomLength);

        return $prefix.substr(bin2hex(random_bytes($randomLength)), 0, $randomLength);
    }
}
