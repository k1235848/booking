<?php

/**
 * Class Database
 *      資料庫相關方法
 */
require_once 'models/Database.php';

/**
 * 員工報名類別
 *      員工報名相關方法
 */
class Member extends Database
{
    /**
     * 取得活動資料
     *
     * @param   string  $id 活動id
     * @return  array
     */
    public function getActivity($id)
    {
        // 搜尋活動資訊
        $sql = "SELECT * FROM `activity` WHERE `aID` = :id";
        $result = $this->prepare($sql);
        $result->bindParam('id', $id);
        $result->execute();

        $row = $result->fetch();
        $showData = ['id'=>$row['aID'], 'name'=>$row['aName'],
                    'content'=>$row['aContent'], 'persons'=>$row['aPersons'],
                    'bring'=>$row['aBringPersons'], 'start'=>$row['aStartTime'],
                    'end'=>$row['aEndTime'], 'competence'=>$row['aCompetence'],
                    'limit'=>$row['aLimit'], 'remain'=>$row['aRemain']];

        return $showData;
    }

    /**
     * 取得活動剩餘人數
     *
     * @param string $id 活動id
     * @return int
     */
    public function getActivityPersons($id)
    {
        // 搜尋活動資訊
        $sql = "SELECT * FROM `activity` WHERE `aID` = :id";
        $result = $this->prepare($sql);
        $result->bindParam('id', $id);
        $result->execute();

        $row = $result->fetch();

        return $row['aRemain'];

    }

    /**
     * 回傳資料庫中會員的權限
     *
     * @param string $mID 會員id
     * @param string $name 會員名稱
     * @return int
     */
    public function getMemberCompetence($mID, $name)
    {
        // 搜尋員工權限
        $sql = "SELECT `mCompetence` FROM `members` " .
        "WHERE `mID` = :id AND `mName` = :name";
        $result = $this->prepare($sql);
        $result->bindParam('id', $mID);
        $result->bindParam('name', $name);
        $result->execute();

        $row = $result->fetch();

        return $row['mCompetence'];
    }

    /**
     * 檢查報名資料 檢查OK將資料寫入資料庫
     *
     * @param string $aID 活動id
     * @param string $mID 會員id
     * @param string $bring  攜帶的人數
     * @param string $mCompetence 會員權限
     * @return string|null
     */
    public function signUpActivity($aID, $mID, $bring, $mCompetence)
    {
        try {
            $this->transaction();

            // 搜尋報名員工
            $sql = "SELECT * FROM `signUpList` " .
            "WHERE `aID` = :aID AND `mID` = :mID";
            $result = $this->prepare($sql);
            $result->bindParam('aID', $aID);
            $result->bindParam('mID', $mID);
            $result->execute();

            if ($result->rowCount() > 0) {
                throw new Exception('已報名過');
            }

            // 搜尋活動資訊
            $sql = "SELECT * FROM `activity` WHERE `aID` = :id FOR UPDATE";
            $result = $this->prepare($sql);
            $result->bindParam('id', $aID);
            $result->execute();

            // 取得剩餘人數、可攜伴人數、限定權限、限制員工、開始時間、截止時間
            $row = $result->fetch();
            $remain = $row['aRemain'];
            $aBring = $row['aBringPersons'];
            $aCompetence = $row['aCompetence'];
            $limit = $row['aLimit'];
            $start = $row['aStartTime'];
            $end = $row['aEndTime'];

            // 檢查報名時間
            if (!($this->checkTime($start,$end))) {
                throw new Exception('不在可報名時間');
            }
            // 檢查攜帶人數
            if ($bring > $aBring) {
                throw new Exception('超過攜帶人數');
            }

            // 檢查剩餘人數
            if (($remain < 1) || (($remain - ($bring + 1)) < 0)) {
                throw new Exception('超過可報名人數');
            }

            // 檢查限制
            if (($limit != null) || ($limit != '')) {
                $limit = explode(',', $limit);
                if (!in_array($mID, $limit)) {
                    throw new Exception('非可報名員工');
                }
            }

            // 檢查權限
            if ($aCompetence != $mCompetence) {
                throw new Exception('非可報名權限');
            }

            $persons = $bring+1;
            // 更新剩餘人數
            $sql = "UPDATE `activity` SET `aRemain` = `aRemain`- :persons " .
            "WHERE `aID` = :id";
            $sth = $this->prepare($sql);
            $sth->bindParam('id' ,$aID);
            $sth->bindParam('persons', $persons);

            if (!$sth->execute()) {
                throw new Exception('報名失敗');
            }

            // 新增報名員工
            $sql = "INSERT INTO `signUpList`(`aID`, `mID`, `persons`) " .
            "VALUES (:aID, :mID, :persons)";
            $sth = $this->prepare($sql);
            $sth->bindParam('aID', $aID);
            $sth->bindParam('mID', $mID);
            $sth->bindParam('persons', $persons);

            if (!$sth->execute()) {
                throw new Exception('報名失敗');
            }

            $this->commit();
        } catch(Exception $e) {
            $this->rollBack();
            $error = $e->getMessage();

            return $error;
        }
    }

    /**
     * 檢查現在時間是否在可報名的時間範圍內
     *
     * @param string $start 報名開始時間
     * @param string $end 報名截止時間
     * @return bool
     */
    public function checkTime($start, $end)
    {
        date_default_timezone_set('Asia/Taipei');
        $distanceStart = time() - strtotime($start);
        $distanceEnd = time() - strtotime($end);

        if (($distanceStart < 0) || ($distanceEnd > 0)){
            return false;
        }else {
            return true;
        }
    }
}

?>