<?php

    namespace Hcode\Model;

    use \Hcode\DB\Sql;
    use \Hcode\Model;
    use \Hcode\Mailer;
    use \Hcode\Tools;

    class User extends Model {

        const SESSION = "User";
        const SECRET = "HcodePhp7_Secret";
        const ERROR = "UserError";
        const ERROR_REGISTER = "UserErrorRegister";
        const SUCCESS = "UserSuccess";

        public static function getFromSession()
        {
            $user = new User();

            if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

                $user->setData($_SESSION[User::SESSION]);
            }

            return $user;
        }

        public static function checkLogin($inadmin = true)
        {
            if (!isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION] || !(int)$_SESSION[User::SESSION]["iduser"] > 0) {
                //Não está logado
                return false;
            } else {
    
                if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
    
                    return true;
                } else if ($inadmin === false) {
    
                    return true;
                } else {
    
                    return false;
                }
            }
        }

        public static function login($Login, $password) 
        {
            $sql = new Sql();

            $results = $sql->select("SELECT * FROM 
                                        tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson 
                                    WHERE a.deslogin = :LOGIN", [
                ":LOGIN" => $Login
            ]);

            if(count($results) == 0) {
                throw new \Exception("Usuário inexistente ou senha inválida.");
            }

            $data = $results[0];

            if(password_verify($password, $data["despassword"]) === true) {

                $user = new User();

                $data['desperson'] = utf8_encode($data['desperson']);

                $user->setData($data);

                $_SESSION[User::SESSION] = $user->getValues();

                return $user;

            }else {

                throw new \Exception("Usuário inexistente ou senha inválida.");
            }
        }
        
        public static function verifyLogin($inadmin = true)
        {
            if (!User::checkLogin($inadmin)) {

                if ($inadmin) {
                    header("Location: /admin/login");
                } else {
                    header("Location: /login");
                }
                exit;
            }
        }

        public static function logout()
        {
            $_SESSION[User::SESSION] = NULL;
        }

        public static function listAll()
        {
            $sql = new Sql();

           return  $sql->select("SELECT*FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
        }

        public function save()
        {
            $sql = new Sql();

           $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
                ':desperson' => utf8_decode($this->getdesperson()),
                ':deslogin' => $this->getdeslogin(),
                ':despassword' =>User::getPasswordHash($this->getdespassword()),
                ':desemail' => $this->getdesemail(),
                ':nrphone' => $this->getnrphone(),
                ':inadmin' => $this->getinadmin()
            ]);

            $this->setData($results[0]);
        }

        public function get($iduser)
        {
            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", [
                ":iduser" => $iduser
            ]);

            $data = $results;

            //$data['desperson'] = utf8_encode($data['desperson']);

            $this->setData($data);
        }

        public function update()
        {
            $sql = new Sql();

            $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
                ':iduser' => $this->getiduser(),
                ':desperson' => utf8_decode($this->getdesperson()),
                ':deslogin' => $this->getdeslogin(),
                ':despassword' => User::getPasswordHash($this->getdespassword()),
                ':desemail' => $this->getdesemail(),
                ':nrphone' => $this->getnrphone(),
                ':inadmin' => $this->getinadmin()
             ]);
 
             $this->setData($results);
        }

        public function delete()
        {
            $sql = new Sql();

            $sql->query("CALL sp_users_delete(:iduser)", [
                ':iduser' => $this->getiduser()
            ]);
        }

        public static function getForgot($email, $inadmin = true)
        {
            $sql = new Sql();

            $results = $sql->select("SELECT 
                    *
                FROM
                    tb_persons a
                    JOIN tb_users USING(idperson)
                WHERE 
                    a.desemail = :email", [
                        ":email" => $email
            ]);
            
            if(count($results) == 0) {

                throw new \Exception("Não foi possível recuperar a senha.");
            }

            $data = $results[0];

            $resultsSP = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", [
                ':iduser' => $data['iduser'],
                ':desip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            if(count($resultsSP) == 0) {

                throw new \Exception("Não foi possível recuperar a senha.");
            }

            $dataRecovery = $resultsSP[0];
            $encrypt = Tools::encrypt_decrypt('encrypt', $dataRecovery['idrecovery'], User::SECRET);

            if($inadmin === true) {

                $link = "http://www.spartanstore.com.br/admin/forgot/reset?code=$encrypt";
            }else{
                $link = "http://www.spartanstore.com.br/forgot/reset?code=$encrypt";
            }   

            $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir Senha da Hcode Store", "forgot", [
                "name" => $data['desperson'],
                "link" => $link
            ]);

            $mailer->send();

            return $data;
        }
        
        public static function validForgotDecrypt($code)
        {
            $idRecovery = Tools::encrypt_decrypt('decrypt', $code, User::SECRET);

            $sql = new Sql();
            $results = $sql->select("SELECT * FROM
                    tb_userspasswordsrecoveries a
                    JOIN tb_users b USING(iduser)
                    JOIN tb_persons c USING(idperson)
                WHERE 
                    a.idrecovery = :idRecovery
                    AND a.dtrecovery IS NULL
                    AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
            ",[
                ":idRecovery" => $idRecovery
            ]);

            if(count($results) == 0) {
                throw new \Exception("Não foi possivel recuperar a senha.");
            }

            return $results[0];
        }

        public static function setForgotUsed($idRecovery)
        {
            $sql = new Sql();

            $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :id", [
                "id" => $idRecovery
            ]);
        }

        public function setPassword($password)
        {
            $sql = new Sql();
            $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", [
                ":password" => $password,
                ":iduser" => $this->getiduser()
            ]);
        }

        public static function setError($msg)
        {
            $_SESSION[User::ERROR] = $msg;
        }

        public static function clearError()
        {
            $_SESSION[User::ERROR] = NULL;
        }

        public static function getError()
        {
            $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';
            
            User::clearError();

            return $msg;
        }

        public static function getPasswordHash($password)
        {
    
            return password_hash($password, PASSWORD_DEFAULT, [
                'cost'=>12
            ]);
        }

        public static function setErrorRegister($msg)
        {
            $_SESSION[User::ERROR_REGISTER] = $msg;
        }

        public static function clearErrorRegister()
        {
            $_SESSION[User::ERROR_REGISTER] = NULL;
        }

        public static function getErrorRegister()
        {
            $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

            User::clearErrorRegister();

            return $msg;
        }

        public static function checkLoginExist($login)
        {
            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
                ':deslogin' => $login
            ]);

            return (count($results) > 0);
        }

        public static function setSuccess($msg)
        {
            $_SESSION[User::SUCCESS] = $msg;
        }

        public static function clearSuccess()
        {
            $_SESSION[User::SUCCESS] = NULL;
        }

        public static function getSuccess()
        {
            $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

            User::clearErrorRegister();

            return $msg;
        }
    }

?>