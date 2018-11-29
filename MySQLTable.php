<?php
namespace phMysql;
/**
 * A class that represents MySQL table.
 *
 * @author Ibrahim <ibinshikh@hotmail.com>
 * @version 1.6
 */
class MySQLTable {
    /**
     * Version number of MySQL server.
     * @var string 
     */
    private $mysqlVnum;
    /**
     * A constant that is returned by some functions to tell that the 
     * table does not have a given column name.
     * @var string 
     * @since 1.4
     */
    const NO_SUCH_COL = 'no_such_col';
    /**
     * A constant that is returned by some functions to tell that the 
     * name of the table is invalid.
     * @var string 
     * @since 1.4
     */
    const INV_TABLE_NAME = 'inv_table_name';
    /**
     * The order of the table in the database.
     * @var int The order of the table in the database. The value 
     * of this attributes describes the dependencies between tables. For example, 
     * if we have three tables, 'A', 'B' and 'C'. Let's assume that table 'B' 
     * references table 'A' and Table 'A' references table 'C'. In this case, 
     * table 'C' will have order 0, Table 'A' have order 1 and table 'B' have order 
     * 2.
     * @since 1.3 
     */
    private $order;
    /**
     * An array that contains all table foreign keys.
     * @var array 
     * @since 1.0
     */
    private $foreignKeys = array();
    /**
     * The name of the table.
     * @var string
     * @since 1.0 
     */
    private $tableName;
    /**
     * An array of table columns.
     * @var array
     * @since 1.0 
     */
    private $colSet = array();
    /**
     * The engine that will be used by the table.
     * @var string
     * @since 1.0 
     */
    private $engin;
    /**
     * Character set of the table.
     * @var string
     * @since 1.0 
     */
    private $charSet;
    /**
     * Creates a new instance of the class.
     * @param string $tName [Optional] The name of the table. It must be a 
     * string and its not empty. Also it must not contain any spaces or any 
     * characters other than A-Z, a-z and underscore. If the given name is invalid 
     * or not provided, 'table' will be used as default.
     */
    public function __construct($tName = 'table') {
        if($this->setName($tName) !== TRUE){
            $this->setName('table');
        }
        $this->mysqlVnum = '5.5';
        $this->engin = 'InnoDB';
        $this->charSet = 'utf8mb4';
        $this->order = 0;
    }
    /**
     * Sets version number of MySQL server.
     * @param string $vNum MySQL version number (such as '5.5').
     * @since 1.6.1
     */
    public function setMySQLVersion($vNum) {
        if(strlen($vNum) > 0){
            $split = explode('.', $vNum);
            if(count($split) >= 2){
                $major = intval($split[0]);
                $minor = intval($split[1]);
                if($major >= 0 && $minor >= 0){
                    $this->mysqlVnum = $vNum;
                }
            }
        }
    }
    /**
     * Returns version number of MySQL server.
     * @return string MySQL version number (such as '5.5'). If version number 
     * is not set, The default return value is '5.5'.
     * @since 1.6.1
     */
    public function getMySQLVersion() {
        return $this->mysqlVnum;
    }
    /**
     * Sets the order of the table in the database.
     * @param int $val The order of the table in the database. The value 
     * of this attributes describes the dependencies between tables. For example, 
     * if we have three tables, 'A', 'B' and 'C'. Let's assume that table 'B' 
     * references table 'A' and Table 'A' references table 'C'. In this case, 
     * table 'C' will have order 0, Table 'A' have order 1 and table 'B' have order 
     * 2.
     * @since 1.3 
     * @return boolean TRUE if the value of the attribute is set. 
     * FALSE if not.
     */
    public function setOrder($val){
        if(gettype($val) == 'integer'){
            if($val > -1){
                $this->order = $val;
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * Returns the order of the table in the database.
     * @return int The order of the table in the database. The value 
     * of this attributes describes the dependencies between tables. For example, 
     * if we have three tables, 'A', 'B' and 'C'. Let's assume that table 'B' 
     * references table 'A' and Table 'A' references table 'C'. In this case, 
     * table 'C' will have order 0, Table 'A' have order 1 and table 'B' have order 
     * 2.
     * @since 1.3 
     */
    public function getOrder() {
        return $this->order;
    }
    /**
     * Returns the value of table collation.
     * @return string If MySQL version is '5.5' or lower, the function will 
     * return 'utf8mb4_unicode_ci'. Other than that, the function will return 
     * 'utf8mb4_unicode_520_ci'.
     * @since 1.6
     */
    public function getCollation(){
        $split = explode('.', $this->getMySQLVersion());
        if(isset($split[0]) && intval($split[0]) <= 5 && isset($split[1]) && intval($split[1]) <= 5){
            return 'utf8mb4_unicode_ci';
        }
        return 'utf8mb4_unicode_520_ci';
    }
    /**
     * Adds a foreign key to the table.
     * @param ForeignKey $key an object of type 'ForeignKey'. Note that it 
     * will be added only if no key was added to the table which has the same name 
     * as the given key.
     * @since 1.1
     * @return boolean TRUE if the key is added. FALSE otherwise.
     * @see ForeignKey
     * @since 1.0
     */
    public function addForeignKey($key){
        if($key instanceof ForeignKey){
            foreach ($this->forignKeys() as $val){
                if($key->getKeyName() == $val->getKeyName()){
                    return FALSE;
                }
            }
            $key->setSourceTable($this->getName());
            array_push($this->foreignKeys, $key);
            return TRUE;
        }
        return FALSE;
    }
    /**
     * Returns the name of table primary key.
     * @return string The returned value will be the name of the table added 
     * to it the suffix '_pk'.
     * @since 1.5
     */
    public function getPrimaryKeyName() {
        return $this->getName().'_pk';
    }
    /**
     * Returns the number of columns that will act as one primary key.
     * @return int The number of columns that will act as one primary key. If 
     * the table has no primary key, the function will return 0. If one column 
     * is used as primary, the function will return 1. If two, the function 
     * will return 2 and so on.
     * @since 1.5
     */
    public function primaryKeyColsCount(){
        $count = 0;
        foreach ($this->colSet as $col){
            if($col->isPrimary()){
                $count++;
            }
        }
        return $count;
    }
    /**
     * Returns a string that can be used to alter the table and add primary 
     * key constraint to it.
     * @return string A string that can be used to alter the table and add primary 
     * key constraint to it. If the table has no primary keys or has only one, 
     * the returned string will be empty.
     * @since 1.5
     */
    public function getCreatePrimaryKeyStatement() {
        $primaryCount = $this->primaryKeyColsCount();
        if($primaryCount == 1){
            return '';
        }
        else if($primaryCount != 0){
            $stm = 'alter table '.$this->getName().' add constraint '.$this->getPrimaryKeyName().' primary key (';
            $index = 0;
            foreach ($this->colSet as $col){
                if($col->isPrimary()){
                    if($index + 1 == $primaryCount){
                        $stm .= $col->getName().')';
                    }
                    else{
                        $stm .= $col->getName().',';
                    }
                    $index++;
                }
            }
            return $stm;
        }
        else{
            return '';
        }
    }
    /**
     * Adds a foreign key to the table.
     * @param MySQLTable $refTable The table that will be referenced.
     * @param string $refColName The name of the column that will be referenced. It must 
     * be a column in the referenced table. The value of this attribute is a 
     * value that once passed to the function Table::getColumn() will 
     * return an object of type 'Column'.
     * @param string $targetCol The target column. It must be a column in the current 
     * instance. The value of this attribute is a 
     * value that once passed to the function Table::getColumn() will 
     * return an object of type 'Column'.
     * @param string $keyname The name of the foreign key.
     * @param string $onupdate [Optional] The 'on update' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * @param string $ondelete [Optional] The 'on delete' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * @return boolean TRUE if the key is added. FALSE otherwise.
     * @see ForeignKey
     * @since 1.0
     */
    public function addReference($refTable,$refColName,$targetCol,$keyname,$onupdate='set null',$ondelete='set null'){
        return $this->addMultiReference($refTable, array($refColName), array($targetCol), $keyname, $onupdate, $ondelete);
    }
    /**
     * Adds a foreign key which references multiple columns.
     * @param MySQLTable $refTable The referenced table.
     * @param array $refColsArr An array which contains the names of referenced 
     * columns. The names of columns  must in the referenced table. 
     * If one of the names is passed to the function Table::getColumn(), it 
     * should return an object of type 'Column'.
     * @param array $targetColsArr An array which contains the targeted columns. 
     * @param string $keyname The name of the key.
     * @param string $onupdate [Optional] The 'on update' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * @param string $ondelete [Optional] The 'on delete' condition for the key. it can be one 
     * of the following: 
     * <ul>
     * <li>set null</li>
     * <li>cascade</li>
     * <li>restrict</li>
     * <li>set default</li>
     * <li>no action</li>
     * </ul>
     * Default value is 'set null'.
     * @return boolean
     * @since 1.5
     */
    public function addMultiReference($refTable,$refColsArr,$targetColsArr,$keyname,$onupdate='set null',$ondelete='set null') {
        if($refTable instanceof MySQLTable){
            if(count($refColsArr) == count($targetColsArr)){
                $fk = new ForeignKey();
                if($fk->setKeyName($keyname) === TRUE){
                    $hasAllCols = TRUE;
                    foreach ($refColsArr as $col){
                        $hasAllCols = $hasAllCols && $refTable->hasColumn($col);
                    }
                    if($hasAllCols){
                        $allAdded = TRUE;
                        foreach ($refColsArr as $col){
                            $allAdded = $allAdded && $fk->addReferenceCol($refTable->getCol($col)->getName());
                        }
                        if($allAdded){
                            if($fk->setReferenceTable($refTable->getName()) === TRUE){
                                foreach ($targetColsArr as $col){
                                    $hasAllCols = $hasAllCols && $this->hasColumn($col);
                                }
                                if($hasAllCols){
                                    foreach ($targetColsArr as $col){
                                        $allAdded = $allAdded && $fk->addSourceCol($this->getCol($col)->getName());
                                    }
                                    if($allAdded){
                                        $fk->setOnDelete($ondelete);
                                        $fk->setOnUpdate($onupdate);
                                        return $this->addForeignKey($fk);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return FALSE;
    }
    /**
     * Returns the columns of the table which are a part of the primary key.
     * @return array An array which contains an objects of type 'Column'. If 
     * the table has no primary key, the array will be empty.
     * @since 1.5.1
     */
    public function getPrimaryKeyCols() {
        $arr = array();
        foreach ($this->columns() as $col){
            if($col->isPrimary()){
                $arr[] = $col;
            }
        }
        return $arr;
    }
    /**
     * Checks if a foreign key with the given name exist on the table or not.
     * @param string $keyName The name of the key.
     * @return boolean TRUE if the table has a foreign key with the given name. 
     * FALSE if not.
     * @since 1.4
     */
    public function hasForeignKey($keyName){
        foreach ($this->forignKeys() as $val){
            if($keyName == $val->getKeyName()){
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * Returns an array that contains all table foreign keys.
     * @return array An array of FKs.
     * @since 1.1
     */
    public function forignKeys() {
        return $this->foreignKeys;
    }
    /**
     * Returns an array of all the columns in the table.
     * @return array An array that contains an objects of type <b>Column</b>
     * @since 1.0
     */
    public function columns(){
        return $this->colSet;
    }

    /**
     * Returns the name of the table.
     * @return string The name of the table.
     * @since 1.0
     */
    public function getName(){
        return $this->tableName;
    }
    /**
     * Sets the name of the table.
     * @param string $param The name of the table (such as 'users'). It must be a 
     * string and its not empty. Also it must not contain any spaces or any 
     * characters other than A-Z, a-z and underscore.
     * @return boolean TRUE if the name of the table is set. FALSE 
     * in case the given name is invalid.
     * @since 1.0
     */
    public function setName($param) {
        if(gettype($param) == 'string'){
            if(strlen($param) != 0){
                if(strpos($param, ' ') === FALSE){
                    for ($x = 0 ; $x < strlen($param) ; $x++){
                        $ch = $param[$x];
                        if($ch == '_' || ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9')){
                            
                        }
                        else{
                            return FALSE;
                        }
                    }
                    $this->tableName = $param;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    /**
     * Adds new column to the table.
     * @param string $key The index at which the column will be added to.
     * @param Column $col An object of type Column. Note that the column will 
     * be added only if no column was found in the table which has the same name 
     * as the given column.
     * @return boolean TRUE if the column is added. FALSE otherwise.
     * @since 1.0
     */
    public function addColumn($key,$col) {
        if(strlen($key) != 0){
            if($col instanceof Column){
                foreach ($this->columns() as $val){
                    if($val->getName() == $col->getName()){
                        return FALSE;
                    }
                }
                $col->setOwner($this);
                $this->colSet[$key] = $col;
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * Checks if the table has a column or not.
     * @param string $colKey The index at which the column might be exist.
     * @return boolean TRUE if the column exist. FALSE otherwise.
     * @since 1.4
     */
    public function hasColumn($colKey) {
        return isset($this->colSet[$colKey]);
    }
    /**
     * Returns the column object given the key that it was stored in.
     * @param string $key The name of the column key.
     * @return Column|NULL A reference to an object of type Column if the given 
     * column was found. NULL in case of no column was found.
     * @since 1.0
     */
    public function &getCol($key){
        if(isset($this->colSet[$key])){
            return $this->colSet[$key];
        }
        $null = NULL;
        return $null;
    }
    /**
     * Returns the index of a column given its key.
     * @param string $key The name of the column key.
     * @return Column|NULL The index of the column if a column was 
     * found which has the given key. -1 in case of no column was found.
     * @since 1.6
     */
    public function getColIndex($key){
        if(isset($this->colSet[$key])){
            return $this->colSet[$key]->getIndex();
        }
        return -1;
    }
    /**
     * Returns a column given its index.
     * @param int $index The index of the column.
     * @return Column|NULL If a column was found which has the specified index, 
     * it is returned. Other than that, The function will return NULL.
     * @since 1.6
     */
    public function &getColByIndex($index){
        foreach ($this->colSet as $k => $col){
            if($col->getIndex() == $index){
                return $col;
            }
        }
        $null = NULL;
        return $null;
    }
    /**
     * Returns an array that contains all the keys the columns was stored in 
     * the table.
     * @return array an array that contains all the set of keys.
     * @since 1.2
     */
    public function colsKeys(){
        return array_keys($this->colSet);
    }
    /**
     * Returns the name of the storage engine used by the table.
     * @return string The name of the storage engine used by the table. The default 
     * value is 'InnoDB'.
     * @since 1.0
     */
    public function getEngine(){
        return $this->engin;
    }
    /**
     * Returns the character set that is used by the table.
     * @return string The character set that is used by the table.. The default 
     * value is 'utf8'.
     * @since 1.0
     */
    public function getCharSet(){
        return $this->charSet;
    }
}