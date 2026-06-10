<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$route = $_GET['route'] ?? 'dashboard';
$routes = [
    'login' => [AuthController::class,'login'],
    'logout' => [AuthController::class,'logout'],
    'dashboard' => [DashboardController::class,'index'],
    'companies' => [CompaniesController::class,'index'],
    'company.save' => [CompaniesController::class,'save'],
    'company.import' => [CompaniesController::class,'import'],
    'company.delete' => [CompaniesController::class,'delete'],
    'cycles' => [CyclesController::class,'index'],
    'cycle.save' => [CyclesController::class,'save'],
    'cycle.finalize' => [CyclesController::class,'finalize'],
    'sources' => [SourcesController::class,'index'],
    'source.save' => [SourcesController::class,'save'],
    'source.delete' => [SourcesController::class,'delete'],
    'financials' => [FinancialsController::class,'index'],
    'financial.import' => [FinancialsController::class,'import'],
    'financial.delete' => [FinancialsController::class,'delete'],
    'prices' => [PricesController::class,'index'],
    'price.import' => [PricesController::class,'import'],
    'price.delete' => [PricesController::class,'delete'],
    'screening' => [ScreeningController::class,'index'],
    'screening.run' => [ScreeningController::class,'run'],
    'catalyst.save' => [ScreeningController::class,'saveCatalyst'],
    'technical.save' => [ScreeningController::class,'saveTechnicalReview'],
    'memos' => [MemosController::class,'index'],
    'pillar.save' => [MemosController::class,'savePillar'],
    'valuation.save' => [MemosController::class,'saveValuation'],
    'memo.generate' => [MemosController::class,'generate'],
];
if (!isset($routes[$route])) { http_response_code(404); echo 'Route tidak ditemukan.'; exit; }
[$class,$method] = $routes[$route];
$controller = new $class();
$controller->$method();
