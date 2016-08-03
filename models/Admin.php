<?php
require_once "models/Database.php";

class Admin extends Database {
    /* @return string */  
    function login($id,$password) {
        // 搜尋並比對資料庫中的管理者資料
        $sql = "SELECT * FROM `admin` WHERE `adminID` = :id AND `adminPassword` = :password";
        $result = $this->prepare($sql);
        
        $result->bindParam('id',$id);
        $result->bindParam('password',$password);
        $result->execute();
        
        // 如果搜尋結果為0
        if ( $result->rowCount() == 0) {
            return "輸入帳號或密碼錯誤";
        }
        
        // 處理查詢結果
        while ($row = $result->fetch())
        {
            $admin = $row['adminID'].$row['adminPassword'];
        }
        
        // 將管理者資料存成SESSION
        $_SESSION['admin'] = $admin;
        
        return "OK";
    }
    /* @return bool */  
    function checkLogin() {
        if (isset($_SESSION['admin'])) {
            return true;
        }else {
            return false;
        }
    }
    /* @return string */  
    function logout() {
        // 刪除session
        session_destroy();
        
        return "登出成功";
    }
    /* @return array */  
    function getMemberList(){
        $sql = "SELECT * FROM `members`";
        $result = $this->prepare($sql);
        $result->execute();
        
        // 搜尋結果為0
        if ( $result->rowCount() == 0) {
            return array();
        }
        
        // 處理查詢結果
        while ($row = $result->fetch()) {
            $showData[] = array("id"=>$row['mID'],"name"=>$row['mName'],"competence"=>$row['mCompetence']);
        }
        
        return $showData;
    }
    /* @return array */  
    function getActivityList(){
        $sql = "SELECT * FROM `activity`";
        $result = $this->prepare($sql);
        $result->execute();
        
        // 搜尋結果為0
        if ( $result->rowCount() == 0) {
            return array();
        }
        
        // 處理查詢結果
        while ($row = $result->fetch()) {
            $showData[] = array("id"=>$row['aID'],"name"=>$row['aName'],"persons"=>$row['aPersons'],
                        "bring"=>$row['aBringPersons'],"start"=>$row['aStartTime'],"end"=>$row['aEndTime']);
        }
        
        return $showData;
    }
    /* @return bool */  
    function newActivity($name,$content,$persons,$bring,$start,$end,$competence,$limit){
        $sql = "INSERT INTO `activity`(`aName`,`aContent`,`aPersons`,`aBringPersons`,`aStartTime`,`aEndTime`,`aCompetence`,`aLimit`) VALUES (:name,:content,:persons,:bring,:start,:end,:competence,:limit)";
        $sth = $this->prepare($sql);
        $sth->bindParam("name",$name);
        $sth->bindParam("content",$content);
        $sth->bindParam("persons",$persons);
        $sth->bindParam("bring",$bring);
        $sth->bindParam("start",$start);
        $sth->bindParam("end",$end);
        $sth->bindParam("competence",$competence);
        $sth->bindParam("limit",$limit);
        return $sth->execute();
    }
    /* @return bool */  
    function newMember($id,$name,$competence){
        $sql = "INSERT INTO `members`(`mID`,`mName`,`mCompetence`) VALUES (:id,:name,:competence)";
        $sth = $this->prepare($sql);
        $sth->bindParam("id",$id);
        $sth->bindParam("name",$name);
        $sth->bindParam("competence",$competence);
        return $sth->execute();
    }
}
?>