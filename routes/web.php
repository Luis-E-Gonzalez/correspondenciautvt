<?php

/////////////// SISTEMA DE CORRESPONDECIA UTVT 2022. ///////////////

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AreasController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\EncryptController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\TiposActividadesController;
use App\Http\Controllers\Sistema\Panel\PanelController;
use App\Http\Controllers\Sistema\TipoAreas\TipoAreasController;
use App\Http\Controllers\Graficas\GraficasPorTipoAreaController;
use App\Http\Controllers\Graficas\GraficasDeActividadesCreadasController;
use App\Http\Controllers\CorreoController;
use App\Http\Controllers\MensajesController;


//////////// RUTAS PARA LA PARTE INICIAL DEL SISTEMA. ///////////////

// Código para validar el correo electrónico.
Route::get('pruebaCorreo', [CorreoController::class,'enviarNuevo'])->name('pruebaCorreo');

// Código para borrar el caché del sistema.
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return 'Application cache cleared';
});

// Ejecución de los recordatorios.
Route::get('/run-schedules', function() {
    $exitCode = Artisan::call('schedule:work');
    return 'Application run schedules, recordatorios:send.';
});

 // Eliminación del caché en las rutas.
 Route::get('/route-cache', function() {
    $exitCode = Artisan::call('route:cache');
    return 'Routes cache cleared';
});

// Eliminación del caché en la configuración.
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return 'Config cache cleared';
});

// Eliminación del caché en la vista.
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return 'View cache cleared';
});


//////////////////// RUTAS DEL PANEL #1 /////////////////////////

Route::redirect('/', 'panel');
Auth::routes();
Route::middleware(['auth'])->group(function () {
    Route::get('/panel', [PanelController::class,'panel']);
    Route::get('/panel/get-actividades-hoy/{idu}', [PanelController::class,'getActividadesHoy']);
    Route::get('/panel/get-actividades-pendientes/{idu}', [PanelController::class,'getActividadesPendientes']);
    Route::get('/panel/get-actividades-por-mes/{idu}', [PanelController::class,'getActividadesPorMes']);
    Route::get('/panel/get-actividades-cerradas/{idu}', [PanelController::class,'getActividadesCerradas']);
    Route::get('/panel/get-actividades-en-seguimiento/{idu}', [PanelController::class,'getActividadesEnSeguimiento']);
    Route::get('/panel/get-actividades-completadas', [PanelController::class,'getActividadesCompletadas']);
    Route::get('/panel/get-actividades-en-proceso', [PanelController::class,'getActividadesEnProceso']);
    Route::get('/panel/get-actividades-sin-entregar', [PanelController::class,'getActividadesSinEntregar']);
    Route::get('/panel/get-actividades-con-acuse-de-recibido', [PanelController::class,'getActividadesConAcuseDeRecibido']);
    Route::get('/panel/get-actividades-sin-acuse-de-recibido', [PanelController::class,'getActividadesSinAcuseDeRecibido']);


//////////////////////////////////////////////  U S U A R I O S  ///////////////////////////////////////////////////////////////

    Route::resource('users', UsersController::class, ['names' => 'users']);

    // Ruta para editar el perfil.
    Route::get('editar-perfil', [CuentasController::class, 'editar_perfil'])->name('editar-perfil');
    Route::post('editar-perfil', [CuentasController::class, 'editar_perfil_post'])->name('editar-perfil.post');

    // Tipo de actividades.
    Route::resource('tipos-actividades', TiposActividadesController::class, ['names' => 'tipos-actividades']);

    // Áreas.
    Route::resource('areas', AreasController::class, ['names' => 'areas']);

    // Routas para el seguimiento de actividades.
    Route::get('actividades_asignadas', [SeguimientoController::class,'actividades_asignadas'])->name('actividades_asignadas');
    Route::get('fecha_actividades_asignadas', [SeguimientoController::class,'fecha_actividades_asignadas'])->name('fecha_actividades_asignadas');
    Route::get('DetallesAsignacion/{idac}', [SeguimientoController::class, 'DetallesAsignacion'])->name('DetallesAsignacion');
    Route::get('Seguimiento/{idac}', [SeguimientoController::class, 'Seguimiento'])->name('Seguimiento');
    Route::POST('AgregarSeguimiento', [SeguimientoController::class,'AgregarSeguimiento'])->name('AgregarSeguimiento');
    Route::get('EliminarSeguimiento/{idarse}/{idseac}/{idac}', [SeguimientoController::class, 'EliminarSeguimiento'])->name('EliminarSeguimiento');
    Route::get('DetallesArchivos/{idarc}', [SeguimientoController::class, 'DetallesArchivos'])->name('DetallesArchivos');
    Route::post('aceptarActividad', [SeguimientoController::class,'aceptarActividad'])->name('aceptarActividad');
    Route::post('rechazarActividad', [SeguimientoController::class,'rechazarActividad'])->name('rechazarActividad');

    // Actividades a  realizar.
    Route::get('reporte_actividades', [ActividadesController::class,'reporte_actividades'])->name('reporte_actividades');
    Route::get('fecha_ajax', [ActividadesController::class,'fecha_ajax'])->name('fecha_ajax');
    Route::get('activacion/{id}/{activo}', [ActividadesController::class,'activacion'])->name('activacion');
    Route::get('aprobacion/{id}/{aprobacion}', [ActividadesController::class,'aprobacion'])->name('aprobacion');
    Route::get('actividades', [ActividadesController::class,'actividades'])->name('create_actividades');
    Route::get('tipousuarios', [ActividadesController::class,'tipousuarios'])->name('ajax_tipousuarios');
    Route::get('quitar_ajax', [ActividadesController::class,'quitar_ajax'])->name('quitar_ajax');
    Route::get('quitar_ajax2', [ActividadesController::class,'quitar_ajax2'])->name('quitar_ajax2');
    Route::POST('insert_actividad', [ActividadesController::class,'insert_actividad'])->name('insert_actividad');
    Route::get('actividades_modificacion/{id}', [ActividadesController::class,'actividades_modificacion'])->name('edit_modificacion');
    Route::post('updateRechazo', [ActividadesController::class, 'updateRechazo'])->name('updateRechazo');
    Route::get('EliminarResponsables/{idreac}', [ActividadesController::class, 'EliminarResponsables'])->name('EliminarResponsables');
    Route::get('Detalles/{id}', [ActividadesController::class, 'Detalles'])->name('Detalles');
    Route::get('detallesSeguimiento/{idac}', [ActividadesController::class, 'detallesSeguimiento'])->name('detallesSeguimiento');
    Route::get('DetallesArchivos/{idarseg}', [ActividadesController::class, 'DetallesArchivos'])->name('DetallesArchivos');

    // Ruta para generar archivo PDF.
    Route::get('pdf/{idac}', [ActividadesController::class, 'pdf'])->name('pdf');
    Route::POST('update_actividades', [ActividadesController::class,'update_actividades'])->name('update_actividades');

    // Ruta para el Administrador: Areas y Usuarios.
    Route::resource('admin/areas', AreasController::class, ['names' => 'areas']);
    Route::resource('admin/users', UsersController::class, ['names' => 'users']);

    // Rutas para las actividades.
    Route::get('actividades_creadas/{id}', [ActividadesController::class, 'actividades_creadas'])->name('actividades_creadas');
    Route::get('actividades_pendientes/{id}', [ActividadesController::class, 'actividades_pendientes'])->name('actividades_pendientes');

    // Ruta del filtro en la fecha.
    Route::get('ajax_filtro_fecha', [ActividadesController::class, 'ajax_filtro_fecha'])->name('ajax_filtro_fecha');

    // Agregar actividad desde la Vista Actividades.
    Route::post('add_tipo_actidad', [ActividadesController::class, 'add_tipo_actidad'])->name('add_tipo_actidad');

    // Ruta - Index.
    Route::get('hello', [EncryptController::class,'index']);

    //////////////////////////// RUTAS - GRÁFICAS POR ÁREA. /////////////////////////

    // Ruta - Estadistica inicial de gráficas por Área.
    Route::get('/dashboard/{user}',[GraficasPorTipoAreaController::class,'dashboard']);
    Route::post('/dashboard/{user}',[GraficasPorTipoAreaController::class,'getEstadisticasDeActividades']);

    // Ruta - Actividades completadas.
    Route::post('/dashboard/{user}/get-actividades-completadas', [GraficasPorTipoAreaController::class,'actividadesCompletadas']);

    // Ruta - Actividades en proceso.
    Route::post('/dashboard/{user}/get-actividades-en-proceso', [GraficasPorTipoAreaController::class,'actividadesEnProceso']);

    // Ruta - Actividades sin entregar.
    Route::post('/dashboard/{user}/get-actividades-sin-entregar', [GraficasPorTipoAreaController::class,'actividadesSinEntregar']);

    // Ruta - Actividades con acuse.
    Route::post('/dashboard/{user}/get-actividades-con-acuse-de-recibido', [GraficasPorTipoAreaController::class,'actividadesConAcuseDeRecibido']);
    Route::post('/dashboard/{user}/get-actividades-sin-acuse-de-recibido', [GraficasPorTipoAreaController::class,'actividadesSinAcuseDeRecibido']);
    Route::post('/dashboard/{user}/get-actividades-por-area', [GraficasPorTipoAreaController::class,'getActividadesPorTipoArea']);

    Route::post('/dashboard/{user}/actividades-completadas-en-tiempo', [GraficasPorTipoAreaController::class,'actividadesCompletadasEnTiempo'])->name('actividades.en-tiempo');
    Route::post('/dashboard/{user}/actividades-completadas-fuera-de-tiempo', [GraficasPorTipoAreaController::class,'actividadesCompletadasFueraDeTiempo']);
    Route::post('/dashboard/{user}/actividades-en-proceso-en-tiempo', [GraficasPorTipoAreaController::class,'actividadesEnProcesoEnTiempo']);
    Route::post('/dashboard/{user}/actividades-en-proceso-fuera-de-tiempo', [GraficasPorTipoAreaController::class,'actividadesEnProcesoFueraDeTiempo']);
    Route::get('/seguimiento/{idac}', [GraficasPorTipoAreaController::class,'seguimiento']);

    // Ruta - Estadistica inicial de gráficas de actividades creadas por el usuario.
    Route::get('/dashboard/{user}/actividades-creadas',[GraficasDeActividadesCreadasController::class,'dashboard']);
    Route::post('/dashboard/{user}/actividades-creadas',[GraficasDeActividadesCreadasController::class,'getEstadisticasDeActividades']);

    // Ruta - Actividades completadas.
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-completadas', [GraficasDeActividadesCreadasController::class,'actividadesCompletadas']);

    // Ruta - Actividades en proceso.
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-en-proceso', [GraficasDeActividadesCreadasController::class,'actividadesEnProceso']);

    // Ruta - Actividades sin entregar.
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-sin-entregar', [GraficasDeActividadesCreadasController::class,'actividadesSinEntregar']);

    // Ruta - Actividades con acuse.
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-con-acuse-de-recibido', [GraficasDeActividadesCreadasController::class,'actividadesConAcuseDeRecibido']);
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-sin-acuse-de-recibido', [GraficasDeActividadesCreadasController::class,'actividadesSinAcuseDeRecibido']);
    Route::post('/dashboard/{user}/actividades-creadas/get-actividades-por-area', [GraficasDeActividadesCreadasController::class,'getActividadesPorTipoArea']);

    // Ruta - Actividades concluidas.
    Route::post('/dashboard/{user}/actividades-creadas/actividades-completadas-en-tiempo', [GraficasDeActividadesCreadasController::class,'actividadesCompletadasEnTiempo'])->name('actividades.en-tiempo');
    Route::post('/dashboard/{user}/actividades-creadas/actividades-completadas-fuera-de-tiempo', [GraficasDeActividadesCreadasController::class,'actividadesCompletadasFueraDeTiempo']);
    Route::post('/dashboard/{user}/actividades-creadas/actividades-en-proceso-en-tiempo', [GraficasDeActividadesCreadasController::class,'actividadesEnProcesoEnTiempo']);
    Route::post('/dashboard/{user}/actividades-creadas/actividades-en-proceso-fuera-de-tiempo', [GraficasDeActividadesCreadasController::class,'actividadesEnProcesoFueraDeTiempo']);

    // Ruta - Detalle de una actividad.
    Route::get('/detalle-actividad/{idac}', [GraficasDeActividadesCreadasController::class,'detalleActividad']);

    Route::resource('mensajes', MensajesController::class);

});


Route::get('php', function (){
    phpinfo();
});


