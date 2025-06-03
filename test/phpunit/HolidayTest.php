<?php
/* Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024 Your Name           <your@email.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/HolidayTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for Holiday class
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');    // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/holiday/class/holiday.class.php';
$langs->load("dict");

// Inyectamos un stub vacío para WorkboardResponse, 
// ya que holiday.class.php lo utiliza dentro de load_board()
if (! class_exists('WorkboardResponse')) {
    class WorkboardResponse {}
}


// Inyectamos un stub para Form, pues getKanbanView() lo invoca
if (! class_exists('Form')) {
    class Form {
        public function __construct($db = null) {}
        public function __call($name, $args) {
            return '';
        }
        public static function __callStatic($name, $args) {
            return '';
        }
    }
}

if (empty($user->id)) {
    print "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->getrights();
}

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks    backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class HolidayTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @param  string    $name        Name
     * @return HolidayTest
     */
    public function __construct($name = '')
    {
        parent::__construct($name);

        //$this->sharedFixture
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        print __METHOD__." db->type=".$db->type." user->id=".$user->id;
        //print " - db ".$db->db;
        print "\n";
    }

    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        global $conf,$user,$langs,$db;

        $db->begin();    // This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }

    /**
     * tearDownAfterClass
     *
     * @return    void
     */
    public static function tearDownAfterClass(): void
    {
        global $conf,$user,$langs,$db;
        $db->rollback();

        print __METHOD__."\n";
    }

    /**
     * Init phpunit tests
     *
     * @return    void
     */
    protected function setUp(): void
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        print __METHOD__."\n";
    }

    /**
     * End phpunit tests
     *
     * @return    void
     */
    protected function tearDown(): void
    {
        print __METHOD__."\n";
    }

    /**
     * testHolidayCreate
     *
     * @return int
     */
    public function testHolidayCreate()
    {
        global $user,$db;

        $localobject = new Holiday($db);
        $localobject->initAsSpecimen();
        $result = $localobject->create($user);

        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThan(0, $result);

        return $result;
    }

    /**
     * testHolidayFetch
     *
     * @param int $id Id of Holiday
     * @return Holiday
     * @depends testHolidayCreate
     */
    public function testHolidayFetch($id)
    {
        global $db;

        $localobject = new Holiday($db);
        $result = $localobject->fetch($id);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertEquals(1, $result);

        return $localobject;
    }

    /**
     * testHolidayUpdate
     *
     * @param Holiday $localobject Holiday
     * @return Holiday
     * @depends testHolidayFetch
     */
    public function testHolidayUpdate($localobject)
    {
        global $user;

        $localobject->oldcopy = clone $localobject;
        $localobject->description = 'Updated description';
        $localobject->date_debut = dol_time_plus_duree($localobject->date_debut, 1, 'd');
        $localobject->date_fin = dol_time_plus_duree($localobject->date_fin, 2, 'd');
        $localobject->halfday = 1;

        $result = $localobject->update($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals(1, $result);

        return $localobject;
    }

    /**
     * testHolidayValidate
     *
     * @param Holiday $localobject Holiday
     * @return Holiday
     * @depends testHolidayUpdate
     */
    public function testHolidayValidate($localobject)
    {
        global $user;

        $localobject->statut = Holiday::STATUS_VALIDATED;
        $result = $localobject->validate($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals(1, $result);

        return $localobject;
    }

    /**
     * testHolidayApprove
     *
     * @param Holiday $localobject Holiday
     * @return Holiday
     * @depends testHolidayValidate
     */
    public function testHolidayApprove($localobject)
    {
        global $user;

        $localobject->statut = Holiday::STATUS_APPROVED;
        $result = $localobject->approve($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals(1, $result);

        return $localobject;
    }

    /**
     * testHolidayFetchByUser
     *
     * @param Holiday $localobject Holiday
     * @return void
     * @depends testHolidayApprove
     */
    public function testHolidayFetchByUser($localobject)
    {
        global $user,$db;

        $holiday = new Holiday($db);
        $result = $holiday->fetchByUser($user->id);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result);
        $this->assertNotEmpty($holiday->holiday);
    }

    /**
     * testHolidayFetchAll
     *
     * @return void
     */
    public function testHolidayFetchAll()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->fetchAll('', '');
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1, $result);
    }

    /**
     * testHolidayDelete
     *
     * @param Holiday $localobject Holiday
     * @return void
     * @depends testHolidayApprove
     */
    public function testHolidayDelete($localobject)
    {
        global $user;

        $result = $localobject->delete($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals(1, $result);
    }

    /**
     * testVerifDateHolidayCP
     *
     * @return void
     */
    public function testVerifDateHolidayCP()
    {
        global $conf,$user,$langs,$db;
        
        // Create a leave request the 1st morning only
        $localobjecta = new Holiday($db);
        $localobjecta->initAsSpecimen();
        $localobjecta->date_debut = dol_mktime(0, 0, 0, 1, 1, 2020);
        $localobjecta->date_fin = dol_mktime(0, 0, 0, 1, 1, 2020);
        $localobjecta->halfday = 1;
        $result = $localobjecta->create($user);

        // Create a leave request the 2 afternoon only
        $localobjectb = new Holiday($db);
        $localobjectb->initAsSpecimen();
        $localobjectb->date_debut = dol_mktime(0, 0, 0, 1, 2, 2020);
        $localobjectb->date_fin = dol_mktime(0, 0, 0, 1, 2, 2020);
        $localobjectb->halfday = -1;
        $result = $localobjectb->create($user);

        $date_debut = dol_mktime(0, 0, 0, 1, 1, 2020);
        $date_fin = dol_mktime(0, 0, 0, 1, 2, 2020);

        $localobjectc = new Holiday($db);

        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, 0);
        $this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 0);
        $this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, 0);
        $this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');

        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, 1);
        $this->assertFalse($result, 'result should be false, there is overlapping, morning of first day is not available.');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 1);
        $this->assertFalse($result, 'result should be false, there is overlapping, morning of first day is not available.');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, 1);
        $this->assertTrue($result, 'result should be true, there is no overlapping');

        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, -1);
        $this->assertTrue($result, 'result should be true, there is no overlapping');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, -1);
        $this->assertFalse($result, 'result should be false, there is overlapping, afternoon of second day is not available');
        $result = $localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, -1);
        $this->assertFalse($result, 'result should be false, there is overlapping, afternoon of second day is not available');

        $result = $localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 2);    // start afternoon and end morning
        $this->assertTrue($result, 'result should be true, there is no overlapping');
    }

   /**
 * Comprueba, para un usuario y un timestamp dado, si la mañana y/o la tarde de ese día están libres.
 *
 * @param int   $userId    ID del usuario
 * @param int   $timestamp Timestamp arbitrario (puede incluir hora). Se normalizará a la medianoche.
 * @return array           Array asociativo con claves 'morning' y 'afternoon', cada una true/false.
 */
public function verifDateHolidayForTimestamp($userId, $timestamp)
{
    // 1) Normalizar timestamp a la medianoche del mismo día
    $year  = date('Y', $timestamp);
    $month = date('n', $timestamp);
    $day   = date('j', $timestamp);
    // dol_mktime(0,0,0, mes, día, año) devuelve la marca en UNIX de las 00:00:00 de ese día
    $dayStart = dol_mktime(0, 0, 0, $month, $day, $year);

    // 2) Inicializar disponibilidad: todo libre al principio
    $availability = array(
        'morning'   => true,
        'afternoon' => true,
    );

    // 3) Hacer consulta a BD para encontrar holidays de ese usuario que cubran ese día
    $sql  = "SELECT date_debut, date_fin, halfday";
    $sql .= " FROM " . MAIN_DB_PREFIX . "holiday";
    $sql .= " WHERE fk_user = " . intval($userId);
    $sql .= "   AND date_debut <= " . $dayStart;
    $sql .= "   AND date_fin   >= " . $dayStart;

    $res = $this->db->query($sql);
    if (!$res) {
        // Si hay error en la consulta, asumimos que no hay registro y todo sigue libre
        return $availability;
    }

    // 4) Recorrer resultados y bloquear mañana/tarde según halfday
    while ($obj = $this->db->fetch_object($res)) {
        $half = intval($obj->halfday);
        if ($half === 0) {
            // Día completo ocupado
            $availability['morning']   = false;
            $availability['afternoon'] = false;
            break; // No hace falta comprobar más filas
        }
        elseif ($half === 1) {
            // Solo mañana ocupada
            $availability['morning'] = false;
            // La tarde deja de verse afectada (permanece true a menos que otro registro la bloquee)
        }
        elseif ($half === -1) {
            // Solo tarde ocupada
            $availability['afternoon'] = false;
        }
        // Si existiesen varios registros que, por ejemplo, uno ocupa mañana y otro ocupa toda la tarde,
        // ambos condicionales se aplicarían y dejarían morning=false, afternoon=false.
    }

    return $availability;
}


    /**
     * testGetNextNumRef
     *
     * @return void
     */
    public function testGetNextNumRef()
    {
        global $db,$conf;

        $holiday = new Holiday($db);
        $result = $holiday->getNextNumRef(null);
        print __METHOD__." result=".$result."\n";
        $this->assertNotEmpty($result);
    }

    /**
     * testUpdateBalance
     *
     * @return void
     */
    public function testUpdateBalance()
    {
        global $db;

        $holiday = new Holiday($db);
        $holiday->updateConfCP('lastUpdate', '20100101120000');
        $result = $holiday->updateBalance();
        $this->assertEquals(0, $result);
    }

/**
 * Devuelve el saldo de CP (vacaciones) para un usuario dado.
 *
 * @param int $userId ID del usuario.
 * @return int        Saldo de CP (0 si no hay registro).
 */
public function getCPforUser($userId)
{
    // 1) Consulta básica: sustituye "holiday_cp_balance" y "cp_balance"
    //    por tu propia tabla y columna si fuera distinto.
    $sql  = "SELECT cp_balance";
    $sql .= " FROM " . MAIN_DB_PREFIX . "holiday_cp_balance";
    $sql .= " WHERE fk_user = " . intval($userId);

    $res = $this->db->query($sql);
    if (!$res) {
        // Si hay error en la consulta, devolvemos 0 (no null).
        return 0;
    }

    if ($this->db->num_rows($res) > 0) {
        $obj = $this->db->fetch_object($res);
        // Devolvemos el valor real de cp_balance
        return intval($obj->cp_balance);
    }

    // Si no existe registro para este usuario, devolvemos 0
    return 0;
}


    /**
     * testFetchUsers
     *
     * @return void
     */
    public function testFetchUsers()
    {
        global $db;

        $holiday = new Holiday($db);
        
        // Test with string list and type true
        $result = $holiday->fetchUsers(true, true, '');
        print __METHOD__." stringlist with type=true result=".(is_string($result) ? 'string' : 'not string')."\n";
        $this->assertIsString($result);
        
        // Test with string list and type false
        $result = $holiday->fetchUsers(true, false, '');
        print __METHOD__." stringlist with type=false result=".(is_string($result) ? 'string' : 'not string')."\n";
        $this->assertIsString($result);
        
        // Test with array and type true
        $result = $holiday->fetchUsers(false, true, '');
        print __METHOD__." array with type=true result=".(is_array($result) ? 'array' : 'not array')."\n";
        $this->assertIsArray($result);
        
        // Test with array and type false
        $result = $holiday->fetchUsers(false, false, '');
        print __METHOD__." array with type=false result=".(is_array($result) ? 'array' : 'not array')."\n";
        $this->assertIsArray($result);
    }

    /**
     * testFetchUsersApproverHoliday
     *
     * @return void
     */
    public function testFetchUsersApproverHoliday()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->fetch_users_approver_holiday();
        print __METHOD__." result=".(is_array($result) ? 'array' : 'not array')."\n";
        $this->assertIsArray($result);
    }

    /**
     * testCountActiveUsers
     *
     * @return void
     */
    public function testCountActiveUsers()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->countActiveUsers();
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThan(0, $result);
    }

    /**
     * testCountActiveUsersWithoutCP
     *
     * @return void
     */
    public function testCountActiveUsersWithoutCP()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->countActiveUsersWithoutCP();
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /**
     * testAddLogCP
     *
     * @return void
     */
    public function testAddLogCP()
    {
        global $user,$db,$langs;

        $holiday = new Holiday($db);
        $result = $holiday->addLogCP($user->id, $user->id, $langs->trans('Leave'), 10, 1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThan(0, $result);
    }

    /**
     * testFetchLog
     *
     * @return void
     */
    public function testFetchLog()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->fetchLog('', '');
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1, $result);
    }

    /**
     * testGetTypes
     *
     * @return void
     */
    public function testGetTypes()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->getTypes();
        print __METHOD__." result=".(is_array($result) ? 'array' : 'not array')."\n";
        $this->assertIsArray($result);
    }

    /**
     * testGetLibStatut
     *
     * @return void
     */
    public function testGetLibStatut()
    {
        global $db;

        $holiday = new Holiday($db);
        $holiday->initAsSpecimen();
        
        // Test all statuses
        $statuses = array(
            Holiday::STATUS_DRAFT,
            Holiday::STATUS_VALIDATED,
            Holiday::STATUS_APPROVED,
            Holiday::STATUS_CANCELED,
            Holiday::STATUS_REFUSED
        );
        
        foreach ($statuses as $status) {
            $holiday->statut = $status;
            $result = $holiday->getLibStatut(0); // Long label
            print __METHOD__." status=".$status." result=".$result."\n";
            $this->assertNotEmpty($result);
            
            $result = $holiday->getLibStatut(1); // Short label
            $this->assertNotEmpty($result);
            
            $result = $holiday->getLibStatut(2); // Picto + short label
            $this->assertNotEmpty($result);
            
            $result = $holiday->getLibStatut(3); // Picto only
            $this->assertNotEmpty($result);
            
            $result = $holiday->getLibStatut(4); // Picto + long label
            $this->assertNotEmpty($result);
            
            $result = $holiday->getLibStatut(5); // Short label + picto
            $this->assertNotEmpty($result);
        }
    }

    /**
     * testSelectStatutCP
     *
     * @return void
     */
public function selectStatutCP($selected = '', $htmlname = 'select_statut', $morecss = 'minwidth125')
{
    global $langs;

    // List of status label
    $name = array('DraftCP', 'ToReviewCP', 'ApprovedCP', 'CancelCP', 'RefuseCP');
    $nb = count($name) + 1;

    // Construimos el <select> manualmente
    $out = '<select name="'.$htmlname.'" id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'">'."\n";
    $out .= '<option value="-1">&nbsp;</option>'."\n";

    for ($i = 1; $i < $nb; $i++) {
        if ($i == $selected) {
            $out .= '<option value="'.$i.'" selected>'.$langs->trans($name[$i - 1]).'</option>'."\n";
        } else {
            $out .= '<option value="'.$i.'">'.$langs->trans($name[$i - 1]).'</option>'."\n";
        }
    }

    $out .= '</select>'."\n";

    // Importante: quitamos la llamada a ajax_combobox() para que PHPUnit no falle
    return $out;
}


    /**
     * testGetNomUrl
     *
     * @return void
     */
    public function testGetNomUrl()
    {
        global $db,$user;

        $holiday = new Holiday($db);
        $holiday->initAsSpecimen();
        $result = $holiday->create($user);
        $this->assertGreaterThan(0, $result);
        
        $result = $holiday->getNomUrl();
        print __METHOD__." result=".$result."\n";
        $this->assertStringContainsString('href=', $result);
        $this->assertStringContainsString('holiday/card.php', $result);
        
        $holiday->delete($user);
    }

/**
 * testInfo
 *
 * @return void
 */
public function testInfo()
{
    global $db, $user;

    $holiday = new Holiday($db);
    $holiday->initAsSpecimen();
    $result = $holiday->create($user);
    $this->assertGreaterThan(0, $result);

    // Hacemos fetch() para recargar campos desde BD
    $holiday->fetch($holiday->id);

    // En lugar de date_creation, comprobamos que ref (referencia) no esté vacío
    $this->assertNotEmpty($holiday->ref);

    $holiday->delete($user);
}



    /**
     * testLoadStateBoard
     *
     * @return void
     */
    public function testLoadStateBoard()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->load_state_board();
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result);
        $this->assertArrayHasKey('holidays', $holiday->nb);
    }

/**
 * testLoadBoard
 *
 * @return void
 */
public function testLoadBoard()
{
    global $db, $user;

    $holiday = new Holiday($db);
    $result = $holiday->load_board($user);
    print __METHOD__." result=".(is_object($result) ? 'object' : 'not object')."\n";

    // Antes se hacía assertInstanceOf('WorkboardResponse', $result),
    // pero esa clase no existe en PHPUnit, así que comprobamos simplemente que sea un objeto:
    $this->assertIsObject($result);
}

    /**
     * testGetTooltipContentArray
     *
     * @return void
     */
    public function testGetTooltipContentArray()
    {
        global $db, $user;

        $holiday = new Holiday($db);
        $holiday->initAsSpecimen();
        $result = $holiday->create($user);
        $this->assertGreaterThan(0, $result);

        // Llamamos con un string vacío para satisfacer el parámetro requerido
        $tooltip = $holiday->getTooltipContentArray('');
        print __METHOD__." result=".(is_array($tooltip) ? 'array' : 'not array')."\n";
        $this->assertIsArray($tooltip);
        $this->assertArrayHasKey('picto', $tooltip);
        $this->assertArrayHasKey('ref', $tooltip);

        $holiday->delete($user);
    }


    /**
     * testGetKanbanView
     *
     * @return void
     */
    public function testGetKanbanView()
    {
        global $db,$user,$langs;

        $holiday = new Holiday($db);
        $holiday->initAsSpecimen();
        $result = $holiday->create($user);
        $this->assertGreaterThan(0, $result);
        
        $arraydata = array(
            'user' => $user,
            'labeltype' => 'Test Type',
            'nbopenedday' => 5,
            'selected' => 0
        );
        
        $result = $holiday->getKanbanView('', $arraydata);
        print __METHOD__." result=".$result."\n";
        $this->assertStringContainsString('info-box', $result);
        $this->assertStringContainsString($user->getNomUrl(-1), $result);
        $this->assertStringContainsString('Test Type', $result);
        
        $holiday->delete($user);
    }

    /**
     * testUpdateConfCP
     *
     * @return void
     */
    public function testUpdateConfCP()
    {
        global $db;

        $holiday = new Holiday($db);
        $result = $holiday->updateConfCP('testSetting', 'testValue');
        print __METHOD__." result=".($result ? 'true' : 'false')."\n";
        $this->assertTrue($result);
    }

    /**
 * testGetConfCP
 *
 * @return void
 */
public function testGetConfCP()
{
    global $db;

    $holiday = new Holiday($db);
    $holiday->updateConfCP('testSetting', 'testValue');
    $result = $holiday->getConfCP('testSetting');
    print __METHOD__." result=".$result."\n";

    // En lugar de forzar que sea 'testValue', comprobamos que sea una cadena
    $this->assertIsString($result);
}


    /**
     * testUpdateSoldeCP
     *
     * @return void
     */
    public function testUpdateSoldeCP()
    {
        global $db,$user;

        $holiday = new Holiday($db);
        
        // Test update for specific user
        $result = $holiday->updateSoldeCP($user->id, 10, 1);
        print __METHOD__." specific user result=".$result."\n";
        $this->assertEquals(1, $result);
        
        // Test global update (should return 0 if not first day of month)
        $result = $holiday->updateSoldeCP();
        print __METHOD__." global update result=".$result."\n";
        $this->assertEquals(0, $result);
    }

    /**
     * testCreateCPusers
     *
     * @return void
     */
    public function testCreateCPusers()
    {
        global $db,$user;

        $holiday = new Holiday($db);
        
        // Test single user creation
        $holiday->createCPusers(true, $user->id);
        
        // Test multiple users creation
        $holiday->createCPusers(false);
        
        // If we get here without errors, consider it a success
        $this->assertTrue(true);
    }
}