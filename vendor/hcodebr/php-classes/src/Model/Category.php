<?php

    namespace Hcode\Model;

    use \Hcode\DB\Sql;
    use \Hcode\Model;
    use \Hcode\Mailer;
    use \Hcode\Tools;

    class Category extends Model {

        public static function listAll()
        {
            $sql = new Sql();

           return  $sql->select("SELECT*FROM tb_categories ORDER BY descategory");
        }

        public function save()
        {
            $sql = new Sql();

            $results = $sql->select("CALL sp_categories_save(:idcategory ,:descategory)", [
                'idcategory' => $this->getidcategory(),
                ':descategory' => $this->getdescategory()
            ]);

            $this->setData($results[0]);

            Category::updateFile();
        }

        public function get($idcategory)
        {
            $sql = new Sql();

            $results = $sql->select("SELECT*FROM tb_categories WHERE idcategory = :idcategory", [
                ':idcategory' => $idcategory
            ]);

            $this->setData($results[0]);
        }

        public function delete()
        {
            $sql = new Sql();

            $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
                ':idcategory' => $this->getidcategory()
            ]);

            Category::updateFile();
        }

        public static function updateFile()
        {
            $categories = Category::listAll();

            $html = [];

            foreach ($categories as $row) {
                array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
            }

            file_put_contents($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."categories-menu.html", implode('', $html));
        }

        public function getProducts($reLated = true)
        {
            $sql = new Sql();

            if($reLated === true) {

               return  $sql ->select("SELECT*FROM tb_products WHERE idproduct IN(
                                        SELECT a.idproduct
                                        FROM tb_products a
                                        INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                                        WHERE b.idcategory = :idcategory)", [
                                                ':idcategory' => $this->getidcategory()
                                            ]);
            }

            return $sql ->select("SELECT*FROM tb_products WHERE idproduct  NOT IN(
                                    SELECT a.idproduct
                                    FROM tb_products a
                                    INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                                    WHERE b.idcategory = :idcategory)", [
                                                ':idcategory' => $this->getidcategory()
                                            ]);
        }

        public function addProduct(Products $product)
        {
            $sql = new Sql();

            $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            ]);  
        }

        public function removeProduct(Products $product)
        {
            $sql = new Sql();

            $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            ]);
        }

        public function getProductsPage($page = 1, $itemsPerPage = 8)
        {
            $start = ($page - 1) * $itemsPerPage;

            $sql = new Sql();

            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS * FROM tb_products a
                          INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                          INNER JOIN tb_categories c ON c.idcategory  = b.idcategory
                          WHERE c.idcategory = :idcategory
                          LIMIT $start, $itemsPerPage", [
                              ':idcategory' => $this->getidcategory()
                          ]);
            
            $resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrTotal");

            return [
                'data' => Products::checkList($results),
                'total' => (int)$resultsTotal[0]['nrTotal'],
                'pages' => ceil($resultsTotal[0]['nrTotal'] / $itemsPerPage)
            ];
        }
    }
?>