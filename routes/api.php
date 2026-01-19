<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StockController;

/*| Public*/
Route::post('/auth/login', [AuthController::class, 'login']);

/*| Protected (Auth)*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    | Users (ADMIN ONLY)
    */
    Route::middleware('permission:users.view')->get('/users', [UserController::class, 'index']);
    Route::middleware('permission:users.create')->post('/users', [UserController::class, 'store']);
    Route::middleware('permission:users.view')->get('/users/{user}', [UserController::class, 'show']);
    Route::middleware('permission:users.update')->put('/users/{user}', [UserController::class, 'update']);
    Route::middleware('permission:users.delete')->delete('/users/{user}', [UserController::class, 'destroy']);

    /*
    | Roles (ADMIN ONLY)
    */
    Route::middleware('permission:roles.view')->get('/roles', [RoleController::class, 'index']);
    Route::middleware('permission:roles.create')->post('/roles', [RoleController::class, 'store']);
    Route::middleware('permission:roles.update')->put('/roles/{role}', [RoleController::class, 'update']);
    Route::middleware('permission:roles.delete')->delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::middleware('permission:roles.update')->post('/roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions']);

    /*
    | Permissions (ADMIN ONLY)
    */
    Route::middleware('permission:permissions.view')->get('/permissions', [PermissionController::class, 'index']);
    Route::middleware('permission:permissions.create')->post('/permissions', [PermissionController::class, 'store']);
    Route::middleware('permission:permissions.update')->put('/permissions/{permission}', [PermissionController::class, 'update']);
    Route::middleware('permission:permissions.delete')->delete('/permissions/{permission}', [PermissionController::class, 'destroy']);

    /*
    | Categories
    */
    Route::middleware('permission:categories.view')->get('/categories', [CategoryController::class, 'index']);
    Route::middleware('permission:categories.create')->post('/categories', [CategoryController::class, 'store']);
    Route::middleware('permission:categories.update')->put('/categories/{category}', [CategoryController::class, 'update']);
    Route::middleware('permission:categories.delete')->delete('/categories/{category}', [CategoryController::class, 'destroy']);

    /*
    | Products
    */
    Route::middleware('permission:products.view')->get('/products', [ProductController::class, 'index']);
    Route::middleware('permission:products.create')->post('/products', [ProductController::class, 'store']);
    Route::middleware('permission:products.view')->get('/products/{product}', [ProductController::class, 'show']);
    Route::middleware('permission:products.update')->put('/products/{product}', [ProductController::class, 'update']);
    Route::middleware('permission:products.delete')->delete('/products/{product}', [ProductController::class, 'destroy']);

    /*
    | Suppliers
    */
    Route::middleware('permission:suppliers.view')->get('/suppliers', [SupplierController::class, 'index']);
    Route::middleware('permission:suppliers.create')->post('/suppliers', [SupplierController::class, 'store']);
    Route::middleware('permission:suppliers.update')->put('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::middleware('permission:suppliers.delete')->delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

    /*
    | Purchases
    */
    Route::middleware('permission:purchases.view')->get('/purchases', [PurchaseController::class, 'index']);
    Route::middleware('permission:purchases.create')->post('/purchases', [PurchaseController::class, 'store']);
    Route::middleware('permission:purchases.receive')->post('/purchases/{purchaseId}/receive', [PurchaseController::class, 'receive']);

    /*
    | Sales
    */
    Route::middleware('permission:sales.view')->get('/sales', [SaleController::class, 'index']);
    Route::middleware('permission:sales.create')->post('/sales', [SaleController::class, 'store']);
    Route::middleware('permission:sales.view')->get('/sales/{saleId}', [SaleController::class, 'show']);
    Route::middleware('permission:sales.create')->post('/sales/{sale}/return', [SaleController::class, 'storeReturn']);

    /*
    | Payments
    */
    Route::middleware('permission:payments.create')->post('/payments', [PaymentController::class, 'store']);

    /*
    | Stock
    */
    Route::middleware('permission:stock.increase')->post('/stock/increase', [StockController::class, 'increase']);
    Route::middleware('permission:stock.decrease')->post('/stock/decrease', [StockController::class, 'decrease']);
    Route::middleware('permission:stock.view_alerts')->get('/stock/alerts', [StockController::class, 'alerts']);

    /*
    | Dashboard
    */
    Route::middleware('permission:dashboard.income')->get('/dashboard/income', [DashboardController::class, 'income']);
    Route::middleware('permission:dashboard.products_pie')->get('/dashboard/products-pie', [DashboardController::class, 'productsPie']);
    Route::middleware('permission:dashboard.view')->get('/dashboard/summary', [DashboardController::class, 'summary']);
});
