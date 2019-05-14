<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use InvalidArgumentException;
use App\repositories\EntRepository;
use App\services\EntService;
use App\models\Ent;

/**
 * Handles error for non existent actions
 */
function errorMethodNotAllowed()
{
    http_response_code(405);
    echo json_encode(['message' => 'Action not found']);
    exit(0);
}

/**
 * Handles error for invalid parameters
 */
function errorBadRequest($message = null)
{
    http_response_code(400);
    echo json_encode(['message' => $message ?? 'Invalid parameters']);
    exit(0);
}

/**
 * Handles error when an unexoected error happens
 */
function errorInternalServer($message = null)
{
    http_response_code(500);
    echo json_encode(['message' => $message ?? 'Internal server error']);
    exit(0);
}

/**
 * Extracts data from POST and returns an Ent model
 * @return Ent
 */
function extractEnt(): Ent
{
    if (!isset($_POST['startDate']) || !isset($_POST['endDate']) || !isset($_POST['price']))
        errorBadRequest();

    $dateFormat = 'Y-m-d';
    if(($startDate = \DateTime::createFromFormat($dateFormat, $_POST['startDate'])) === false)
        errorBadRequest('Start Date has a bad format');
    if(($endDate = \DateTime::createFromFormat($dateFormat, $_POST['endDate'])) === false)
        errorBadRequest('End Date has a bad format');
    if(!is_numeric(($price = $_POST['price'])))
        errorBadRequest('Price is not a float number');
    
    $price = floatval($price);

    try {
        $ent = new Ent($startDate, $endDate, $price);
    } catch (InvalidArgumentException $e) {
        errorBadRequest($e->getMessage());
    }

    return $ent;
}

header('Content-Type: application/json');

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if (in_array($action, ['list', 'insert', 'update', 'delete', 'clear'])) {

        $config = require '../config.php';

        try {
            $pdo = new \PDO($config['database']['dsn'], $config['database']['username'], $config['database']['password']);
        } catch (PDOException $e){
            errorInternalServer($e->getMessage());
        }

        $service = new EntService(new EntRepository($pdo));

        $data = [];

        try {
            switch($action) {
                case 'list':
                    $data = $service->findAll();
                    break;
                case 'insert':
                    $ent = extractEnt();
                    $service->save($ent);

                    $data = ['OK'];
                    break;
                case 'delete':
                    if (!(isset($_POST['id']) && is_numeric($id = $_POST['id'])))
                        errorBadRequest();
                    
                    $service->delete(intval($id));
                    $data = ['OK'];
                    break;
                case 'clear':
                    $service->deleteAll();
                    $data = ['OK'];
                    break;
                default:
                    break;
            }
        } catch(\Exception $e) {
            errorInternalServer($e->getMessage());
        }

        $pdo = null;

        echo json_encode(['data' => $data]);
        exit(0);
    }
}

errorMethodNotAllowed();