<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Actividades;
use App\Models\ResponsablesActividades;
use App\Models\Users;
use App\Models\SeguimientosActividades;
use App\Models\TiposActividades;
use Illuminate\Support\Facades\Auth;
use DB;
use Arr;
use PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\enviar_asignacion;

class ActividadesController extends Controller
{
    public function reporte_actividades()
    {
        //Esta funcion es para el rector que ve todas las actividades de los usuarios

        //Variable que recupera el id del usuario de la BD
        $us_id = \Auth()->User()->idu;
        //Consulta que recupera todas las actividades de todos los usuarios
        $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
        ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
        ac.status, ta.nombre AS tipo_actividad
        FROM actividades AS ac
        INNER JOIN users AS us ON us.idu = ac.idu_users
        INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
        INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
        LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
        LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
        WHERE ac.activo = 1
        AND ac.aprobacion = 1
        GROUP BY ac.idac
        ORDER BY ac.fecha_creacion DESC");

        //Lo siguiente es para el uso de la libreria ZingGrid para que se muestren en la tabla
        $array = array();

        //Funcion para mostrar los datos del poncentaje de cada actividad y darle formato(NOTA: el porcentaje de recupera en una funcion en la base de datos)
        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1], '3' => $val[2]);
            } else {
                $arr = null;
            }
            return $arr;
        }

        //funcion para mostrar los botones al final de la tabla
        function btn($idac, $activo)
        {

            return "<a class='btn btn-success btn-sm'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>";
        }

        //funcion para mostrar las fechas
        function AB($data)
        {

            if (gettype($data) == "array") {

                return $data['1'][0] . " de " . $data['1'][1];
            } else {
                return 0;
            }
        }

        //Función para mostrar el porcentaje y se le de formato
        function C($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['2'], 0, '.', ' ') . '%';
            } else {
                return 0;
            }
        }

        //Función para mostrar el porcentaje y se le de formato
        function D($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['3'], 0, '.', ' ') . '%';
            } else {

                return 0;
            }
        }

        //Funcion para condicionar que datos retornara dependiento del dato entrante
        function E($status)
        {

            if ($status == 1) {
                return "En proceso";
            } elseif ($status == 2) {
                return "Concluido";
            } else {
                return "Cancelado";
            }
        }

        //Foreach para insertar en un array todos los datos
        foreach ($consult as $c) {

            $data = recorrer($c->porcentaje);

            array_push($array, array(
                'idac' => $c->idac,
                'turno' => $c->turno,
                'fecha_creacion' => Carbon::parse($c->fecha_creacion)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'asunto' => $c->asunto,
                'nombre_actividad' => $c->tipo_actividad,
                'descripcion' => $c->descripcion,
                'creador' => $c->creador,
                'periodo' => Carbon::parse($c->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [del] YYYY [al]') . Carbon::parse($c->fecha_fin)->locale('es')->isoFormat(' D [de] MMMM [del] YYYY'),
                'importancia' => $c->importancia,
                'nombre' => $c->nombre,
                'activo' => $c->activo,
                'acuse' => $c->acuse,
                'idu_users' => $c->idu_users,
                'avance' => AB($data),
                'atendido_por' =>  D($data),
                'estatus' => E($c->status),
                'operaciones' => btn($c->idac, $c->activo),
            ));
        }

        //Variable para que todo el array anterior se convierta en un JSON
        $json = json_encode($array);

        return view('Actividades.reporte')
                    ->with('json', $json);
    }

    public function fecha_ajax(Request $request)
    {
        //Funcion que controla los AJAX de los filtros de los reporte

        //variable que recupera el id del usuario de la BD
        $us_id = \Auth()->User()->idu;

        //Variable que recupera el request del input fecha_orden
        $fecha_orden =  $request->fecha_orden;

        //Variable que recupera el request del input fecha de inicio
        $fechaIni =  $request->fechaIni;

        //Variable que recupera el request del input fecha fin
        $fechaFin =  $request->fechaFin;

        //Variable que recupera el id del area del usuario
        $ar = Auth()->user()->idar_areas;

        //Condicion que determina si quien esta interactuando es la secretaria y saca las axctividades de quien esta acargo de ella o el
        if (Auth()->user()->idtu_tipos_usuarios == 4) {
            $dir = DB::SELECT("SELECT idu FROM users WHERE idar_areas = $ar AND idtu_tipos_usuarios = 2");
            $id = $dir[0]->idu;
            $us_id = $id;
        }

        //Las siguientes condiciones saca distintas consultas dependiendo que especificaciones se hayan seleccionado

        if ($fecha_orden == 0) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 1 && $fechaIni != NULL && $fechaFin != NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_inicio` BETWEEN  DATE('$fechaIni') AND DATE('$fechaFin')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 1 && $fechaIni != NULL && $fechaFin == NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_inicio` >=  DATE('$fechaIni')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 1 && $fechaIni == NULL && $fechaFin != NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_inicio` <=  DATE('$fechaFin')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 2 && $fechaIni != NULL && $fechaFin != NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_fin` BETWEEN  DATE('$fechaIni') AND DATE('$fechaFin')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 2 && $fechaIni != NULL && $fechaFin == NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_fin` >=  DATE('$fechaIni')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }
        if ($fecha_orden == 2 && $fechaIni == NULL && $fechaFin != NULL) {

            $consult = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio, ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion, porcentaje(ac.idac,$us_id) AS porcentaje,
                ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.`fecha_fin` <=  DATE('$fechaFin')
                AND ac.activo = 1
                AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }


        //variable que crea un array para el uso de la tabla de ZingGrid
        $array = array();


        //Funcion para mostrar los datos del poncentaje de cada actividad y darle formato(NOTA: el porcentaje de recupera en una funcion en la base de datos)
        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1], '3' => $val[2]);
            } else {
                $arr = null;
            }
            return $arr;
        }

        //funcion para que se muestren los botones en la tabla
        function btn($idac, $activo)
        {

            return "<a class='btn btn-success btn-sm'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>";
        }

        //funcion para que se muestren las fechas
        function AB($data)
        {

            if (gettype($data) == "array") {

                return $data['1'][0] . " de " . $data['1'][1];
            } else {
                return 0;
            }
        }

        //funcion para que muestre los porcentajes y se le de formato
        function C($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['2'], 0, '.', ' ') . '%';
            } else {
                return 0;
            }
        }

         //funcion para que muestre los porcentajes y se le de formato
        function D($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['3'], 0, '.', ' ') . '%';
            } else {

                return 0;
            }
        }

        //funcion para que se retorne el estado de la actividad
        function E($status)
        {

            if ($status == 1) {
                return "En proceso";
            } elseif ($status == 2) {
                return "Concluido";
            } else {
                return "Cancelado";
            }
        }

        //Foreach para que guarde todos los datos en el array anteriormente instanciado
        foreach ($consult as $c) {

            $data = recorrer($c->porcentaje);

            array_push($array, array(
                'idac' => $c->idac,
                'turno' => $c->turno,
                'fecha_creacion' => Carbon::parse($c->fecha_creacion)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'asunto' => $c->asunto,
                'nombre_actividad' => $c->tipo_actividad,
                'descripcion' => $c->descripcion,
                'creador' => $c->creador,
                'periodo' => Carbon::parse($c->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [del] YYYY [al]') . Carbon::parse($c->fecha_fin)->locale('es')->isoFormat(' D [de] MMMM [del] YYYY'),
                'importancia' => $c->importancia,
                'nombre' => $c->nombre,
                'activo' => $c->activo,
                'acuse' => $c->acuse,
                'idu_users' => $c->idu_users,
                'avance' => AB($data),
                'atendido_por' =>  D($data),
                'estatus' => E($c->status),
                'operaciones' => btn($c->idac, $c->activo),
            ));
        }

        $json = json_encode($array);
        return response()->json($json);
    }

    public function Detalles($idac)
    {
        //variable que recupera el id
        $idac = decrypt($idac);
        //variable que recupera el id del usuario en sesion
        $us_id = \Auth()->User()->idu;
        //consulta que se mostrara en los reportes
        $query = DB::SELECT("SELECT res.idu_users, ar.nombre AS nombre_ar, CONCAT(us.titulo,' ', us.nombre, ' ', us.app, ' ', us.apm) AS nombre_us,
        res.acuse, res.idreac, seg.estado, Max(seg.porcentaje) as porcentaje, razon_rechazo, ac.fecha_fin, ac.status AS status_ac, us.idtu_tipos_usuarios, Max(DATE(seg.created_at)) as created_at_seg
        FROM responsables_actividades AS res
        JOIN actividades AS ac ON ac.idac = res.idac_actividades
        JOIN users AS us ON us.idu = res.idu_users
        JOIN areas AS ar ON ar.idar = us.idar_areas
        LEFT JOIN seguimientos_actividades AS seg ON seg.idreac_responsables_actividades = res.idreac
        WHERE idac_actividades = $idac
        AND ac.aprobacion = 1
        GROUP BY idu_users");

        //crea una variable del acuse
        $boton = DB::table('responsables_actividades as res')
            ->select(DB::raw('IF(COUNT(res.acuse) = 0, 0 , 1) AS boton'))
            ->where([
                ['res.idac_actividades', '=', $idac],
                ['res.acuse', '=', 1],
            ])
            ->first();


        $array = array();

        //calcula el porcentaje que llevan las actividades
        function por($porcentaje, $acuse)
        {
            if ($acuse == 0) {
                return "0%";
            } elseif ($acuse == 2) {
                return "Acuse rechazado";
            } elseif ($porcentaje == NULL) {
                return "0%";
            } else {
                return $porcentaje . "%";
            }
        }

        //funcion que crea los botones para ver el seguimiento de las actividades asignadas
        function btn($idac, $data, $rechazo, $idreac)
        {
            if ($data == 0) {
                //no existen seguimientos de las actividades asignadas
                return ("No existen detalles");
            } else if ($data == 1) {
                //se crea el boton para visualizar cuando hay uno o mas seguimientos de las actividades
                return "<a href=" . route('detallesSeguimiento', encrypt($idac)) . "><button type='button' class='btn btn-success btn-sm'><i class='nav-icon fas fa-eye'></i></button></a>   ";
            } else if ($data == 2) {
                //si el usuario no tiene los privilegios adecuados solo vera el porque se rechazo la actividad asignada
                if (Auth()->User()->idtu_tipos_usuarios == 3) {
                    return "<a href='#' class='btn btn-danger btn-sm pull-right' data-toggle='modal' data-target='#create$idac'><i class='nav-icon fas fa-eye'></i></a>
                <div class='modal fade' id='create$idac'>
                  <div class='modal-dialog'>
                      <div class='modal-content'>
                          <div class='modal-header'>
                               <h4>Razon del rechazo</h4>
                          </div>
                          <div class='modal-body'>
                                 $rechazo
                                </div>
                          </div>
                     </div>
                 </div>
               </div>";
                } else {
                    //se crea la opcion de asignar nuevamente la actividad o remover al usuario de la actividad asignada
                    return "<a href='#' class='btn btn-danger btn-sm pull-right' data-toggle='modal' data-target='#create$idac'><i class='nav-icon fas fa-eye'></i></a>
                <div class='modal fade' id='create$idac'>
                  <div class='modal-dialog'>
                      <div class='modal-content'>
                          <div class='modal-header'>
                               <h4>Razon del rechazo</h4>
                          </div>
                          <div class='modal-body'>
                                 $rechazo
                                 <form action=" . route('updateRechazo') . " method='POST' enctype='multipart/form-data'>
                                 <input type='hidden' name='_token' value=" . csrf_token() . ">
                                 <input type='hidden' value=" . $idac . " name='idreac'>
                                     <label>Describe la razón del porque si le corresponde la actividad</label>
                                     <Textarea class='form-control' name='razon_activacion' id='razon_a' value='{{old('razon_activacion')}}' rows='5' required></Textarea>
                                     <button type='submit' class='btn btn-sm btn-success'><i class='fas fa-check-circle'></i></button> <a class='btn btn-danger btn-sm' href=" . route('EliminarResponsables', encrypt($idreac)) . "                                               id='boton_disabled' ><i class='nav-icon fas fa-ban'></i></a>
                                </form>
                                </div>
                          </div>
                     </div>
                 </div>
               </div>";
                }
            }
        }


        //funcion que controla los mensajes de finalizacion de la actividad
        function D($porcentaje, $fecha_fin, $created_at, $acuse)
        {

            $date = Carbon::now()->locale('es')->isoFormat("Y-MM-DD");

            //  return ($created_at > $fecha_fin ? "es mayor" : "No es mayor");
            if ($date > $fecha_fin && $created_at == NULL) {
                return "En proceso - Fuera de Tiempo";
            } elseif ($acuse == 2) {

                return "Acuse rechazado";
            } elseif ($date <= $fecha_fin && $porcentaje < 100) {

                return "En proceso – En Tiempo";
            } elseif ($created_at <= $fecha_fin   && $porcentaje == 100) {

                return "Concluido – En tiempo";
            } elseif ($date > $fecha_fin  && $porcentaje < 100) {

                return "En proceso - Fuera de Tiempo";
            } elseif ($created_at > $fecha_fin  && $porcentaje == 100) {

                return "Concluido – Fuera de Tiempo";
            } else {
                return "Sin aceptar estado";
            }
        }

        //funcion que controla los mensajes de aceptado/rechazado/ no recibido
        function Acuse($data)
        {


            if ($data == 1) {
                $acuse = "Recibido";
            } else if ($data == 2) {
                $acuse = "Rechazado";
            } else {
                $acuse = "No recibido";
            }
            return $acuse;
        }

        //query que recorre la tabla para crear el zingrid
        foreach ($query as $c) {

            $data1 = Acuse($c->acuse);
            $porcentaje = por($c->porcentaje, $c->acuse);

            array_push($array, array(
                'nombre_us' => $c->nombre_us,
                'nombre_ar' => $c->nombre_ar,
                'porcentaje' =>  $porcentaje,
                'estado' =>  D($c->porcentaje, $c->fecha_fin, $c->created_at_seg, $c->acuse),
                'acuse' => $data1,
                'operaciones' => btn($c->idreac, $c->acuse, $c->razon_rechazo, $c->idreac, $us_id),
            ));
        }

        //variable que transforma el resultado de la consulta en json
        $json = json_encode($array);

        //regreso de la vista con las variables a mostrar
        return view('Actividades.reporte_detalles')

            ->with('json', $json)
            ->with('idac', $idac)
            ->with('boton', $boton);
    }

    //funcion que actualiza la asignacion nuevamente de la actividad rechazada
    public function updateRechazo(Request $c)
    {
        //variable que recupera el id de la actividad
        $idreac = $c->idreac;
        //variable que muestra el acuse como no leido
        $acuse = 0;
        //vacia el campo en la tabla
        $razon_rechazo = NULL;
        //variable que recupera el mensaje del porque si le corresponde la actividad al usuario
        $razon_activacion = $c->razon_activacion;
        //actualiza los campos en la base
        DB::UPDATE("UPDATE responsables_actividades SET  acuse ='$acuse', razon_rechazo = '$razon_rechazo', razon_activacion = '$razon_activacion'
        WHERE idreac = $idreac");
        return back()->with('message', 'El usuario se ha reactivado en la actividad');
    }

    public function pdf($idac)
    {
        //variable que recupera y desencripta el id
        $idac = decrypt($idac);
        //consulta que trae los datos de la actividad y los directivos que aceptaron dicha actividad
        $data = DB::SELECT("SELECT CONCAT(us.titulo,' ',us.nombre,' ',us.app,' ',us.apm) AS nombre, DATE_FORMAT(res.fecha_acuse,'%d-%m-%Y') AS fecha_acuse, CONCAT(ta.nombre,' / ',ar.nombre) AS area,
        ac.asunto , ac.descripcion , ac.comunicado, ac.fecha_creacion, DATE_FORMAT(ac.fecha_inicio,'%d-%m-%Y') AS fecha_inicio, DATE_FORMAT(ac.fecha_fin,'%d-%m-%Y') AS fecha_fin, SUBSTRING(res.firma, 1, 20) AS firma, SUBSTRING(res.firma, 21, 46) AS firma2
        FROM responsables_actividades AS res
        JOIN users AS us ON us.idu = res.idu_users
        JOIN areas AS ar ON ar.idar = us.idar_areas
        JOIN tipos_areas AS ta ON ta.idtar = ar.idtar
        JOIN actividades AS ac ON ac.idac = res.idac_actividades
        WHERE idac_actividades = $idac
        AND res.acuse = 1");

        //vista que se carga con toda la informacion correspondiente
        $pdf = PDF::loadView('Actividades.pdf', compact('data'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream('PDF de actividades seguimientos.pdf');
    }


    public function detallesSeguimiento($idac)
    {
        //variable que recupera el id de la actividad
        $idac = decrypt($idac);
        //variable que recupera al responsable de la actividad
        $idActvidad = ResponsablesActividades::where('idreac', $idac)->select('idac_actividades')->first();
        //variable que encripta el id de la actividad
        $idActvidad = encrypt($idActvidad->idac_actividades);
        //variable donde se guarda la consulta
        $consult = DB::SELECT("SELECT seg.idseac, DATE(seg.fecha) as fecha, seg.detalle, seg.porcentaje, seg.estado, CONCAT(us.titulo,' ',us.nombre,' ',us.app,' ',us.apm) AS nombre,
        arch.ruta, act.asunto, arch.ruta, ar.nombre as nombre_ar, act.fecha_fin, act.status AS status_ac, us.idtu_tipos_usuarios, re.acuse, re.created_at, seg.archivo_fin
        FROM seguimientos_actividades AS seg
        INNER JOIN responsables_actividades AS re ON re.idreac = seg.idreac_responsables_actividades
        INNER JOIN users AS us ON us.idu = re.idu_users
        INNER JOIN areas AS ar ON us.idar_areas = ar.idar
        INNER JOIN actividades AS act ON re.idac_actividades = act.idac
        INNER JOIN archivos_seguimientos AS arch ON arch.idseac_seguimientos_actividades = seg.idseac
            WHERE idreac_responsables_actividades = $idac
            GROUP BY idseac ASC");

        $array = array();

        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1]);
            } else {
                $arr = null;
            }
            return $arr;
        }
        //funcion que crea los botones para visualizar los archivos subidos a las actividades dependiendo de las condiciones
        function btn($idac, $ruta, $archivo_fin)
        {
            if ($ruta == "Sin archivo" && $archivo_fin == NULL) {
                return "Sin archivos";
            } else if ($ruta != "Sin archivo" && $archivo_fin == NULL) {
                return "<a href='javascript:void(0)' data-toggle='tooltip' data-id=" . encrypt($idac) . "  data-original-title='DetallesArchivos' class='edit btn btn-success btn-sm DetallesArchivos'><i class='nav-icon fas fa-file'></i></a>";
            } else if ($ruta == "Sin archivo" && $archivo_fin != NULL) {
                return "<a download='archivo-finalizacion' href=" . asset("archivos/Seguimientos/$archivo_fin") . " class='ArchivoTermino btn btn-dark btn-sm mt-3'><i class='nav-icon fas fa-file-pdf'></i></a>";
            } else if ($ruta != "Sin archivo" && $archivo_fin != NULL) {
                return "<div class='btn-group me-2' role='group' aria-label='Second group'><a href='javascript:void(0)' data-toggle='tooltip' data-id=" . encrypt($idac) . "  data-original-title='DetallesArchivos' class='edit btn btn-success  mt-3 btn-sm DetallesArchivos'><i class='nav-icon fas fa-file'></i></a>
                &nbsp; <a download='archivo-finalizacion' href=" . asset("archivos/Seguimientos/$archivo_fin") . " class='ArchivoTermino btn btn-dark btn-sm mt-3'><i class='nav-icon fas fa-file-pdf'></i></a></div>";
            }
        }


        //funcion que muestra el estado de la actividad
        function D($porcentaje, $fecha_fin, $created_at, $acuse)
        {

            $date = Carbon::now()->locale('es')->isoFormat("Y-MM-DD");

            //  return ($created_at > $fecha_fin ? "es mayor" : "No es mayor");
            if ($acuse == 2) {

                return "Acuse rechazado";
            } elseif ($created_at <= $fecha_fin && $porcentaje < 100) {

                return "En proceso – En Tiempo";
            } elseif ($created_at <= $fecha_fin   && $porcentaje == 100) {

                return "Concluido – En tiempo";
            } elseif ($created_at > $fecha_fin  && $porcentaje < 100) {

                return "En proceso - Fuera de Tiempo";
            } elseif ($created_at > $fecha_fin  && $porcentaje == 100) {

                return "Concluido – Fuera de Tiempo";
            } else {
                return "Sin aceptar estado";
            }
        }

        $turno = 1;

        //query para crear el zingrid
        foreach ($consult as $c) {



            array_push($array, array(
                'idseac' => $turno,
                'fecha' => Carbon::parse($c->fecha)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'detalle' =>  $c->detalle,
                'estado' =>  D($c->porcentaje, $c->fecha_fin, $c->fecha, $c->estado),
                'porcentaje' => $c->porcentaje . '%',
                'operaciones' => btn($c->idseac, $c->ruta, $c->archivo_fin),
            ));
            $turno = $turno + 1;
        }
        $json = json_encode($array);

        return view('SeguimientoActividades.detallesSeguimiento')
            ->with('json', $json)
            ->with('consult', $consult)
            ->with('id_actividad', $idActvidad);
    }

    //funcion para ver los detalles de las actividades
    public function DetallesArchivos($idarc)
    {
        $idarc = decrypt($idarc);
        $query = DB::SELECT("SELECT res.idarseg, res.nombre, res.detalle_a, res.ruta
        FROM archivos_seguimientos AS res
        INNER JOIN seguimientos_actividades AS seg ON seg.idseac = res.idseac_seguimientos_actividades
        WHERE res.idseac_seguimientos_actividades = $idarc");
        return response()->json($query);
    }

    public function EliminarResponsables($idreac)

    {
        //$ultimo = archivosSeguimientos::find('idarse')->orderBy('idarse')->desc();
        $idreac = decrypt($idreac);


        $elim = DB::DELETE("DELETE FROM responsables_actividades
        where idreac =$idreac
        ");



        return back()->with('message2', 'El usuario se ha eliminado de la actividad');;
    }


    public function actividades()
    {
        //funcion para el modulo de la creacion de actividades

        //Condicion para determinar si la persona que esta haciendo la actividad es asistente o no
        if (Auth()->user()->idtu_tipos_usuarios == 4) {

            //variable que determina el encargado(a) del area de la secretaria
            $dir = DB::SELECT("SELECT idu FROM users
                            WHERE idar_areas = " . Auth()->user()->idar_areas .
                            " AND idtu_tipos_usuarios = 2");

            //variabel que saca los datos del encargado(a) de la secretaria
            $user = DB::table('users')
                ->join('tipos_usuarios', 'tipos_usuarios.idtu', '=', 'users.idtu_tipos_usuarios')
                ->join('areas', 'areas.idar', '=', 'users.idar_areas')
                ->select(
                    'users.idu',
                    'users.titulo',
                    'users.nombre',
                    'users.app',
                    'users.apm',
                    'tipos_usuarios.nombre as tipo_usuario',
                    'areas.nombre as nombre_areas',
                    'areas.idar',
                )
                ->where('users.idu', '=', $dir[0]->idu)
                ->get();

        } else {

            //variable que saca los datos del usuario que no es secretaria(o)
            $user = DB::table('users')
                ->join('tipos_usuarios', 'tipos_usuarios.idtu', '=', 'users.idtu_tipos_usuarios')
                ->join('areas', 'areas.idar', '=', 'users.idar_areas')
                ->select(
                    'users.idu',
                    'users.titulo',
                    'users.nombre',
                    'users.app',
                    'users.apm',
                    'tipos_usuarios.nombre as tipo_usuario',
                    'areas.nombre as nombre_areas',
                    'areas.idar',
                )
                ->where('users.idu', '=', Auth()->user()->idu)
                ->get();
        }

        //variable que saca la fecha actual y le da formato
        $hoy = Carbon::now()->locale('es_MX')->format('d-m-Y');

        //variable que cuenta todos los registros y del total le suma 1
        $consul = DB::table('actividades')->count() + 1;

        //Variable que saca todas las areas en la base de datos
        $tipous = DB::table('areas')->get()->all();

        //variable que saca todas las actividades de la base de datos
        $tipo_actividad = DB::table('tipos_actividades')
            ->orderBy('nombre', 'Asc')
            ->get();



        return view('Actividades.actividades')
            ->with('hoy', $hoy)
            ->with('consul', $consul)
            ->with('tipo_actividad', $tipo_actividad)
            ->with('tipous', $tipous)
            ->with('user', $user);
    }

    public function tipousuarios(Request $request)
    {
        //Funcion para el AJAX del selector de usuarios de actividad

        //variable que saca el valor obtenido del request del select de las areas
        $id = $request->tipo_u;

        //variable que se saca el valor obtenido del request del checkbox si se selecciono o no
        $cor = $request->cor;


        //variable para obtener los datos de las personas que estan en el area
        $consul = DB::Select("SELECT  u.idu,u.email, u.titulo,u.nombre,u.app,u.apm, tu.nombre AS tipo_area, a.nombre AS areas  FROM users AS u
            INNER JOIN tipos_usuarios AS tu ON tu.idtu = u.idtu_tipos_usuarios
            INNER JOIN areas AS a ON a.idar = u.idar_areas
            WHERE u.idtu_tipos_usuarios NOT IN(1)
            AND u.idtu_tipos_usuarios NOT IN(4)
            AND a.idar = $id");

        //Se retornan las consulta y lo obtenido del request del checkbox
        return response()->json([$consul,$cor]);
    }

    public function insert_actividad(Request $r)
    {
        //funcion para insertar las actividades dentro de la base de datos

        //variables que recolecta todo lo que llego en el request
        $idusuario = $r->idusuario;
        $idar_areas = $r->idar_areas;
        $fechacreacion = $r->fechacreacion;
        $turno = $r->turno;
        $comunicado = $r->comunicado;
        $Asunto = $r->Asunto;
        $tipoactividad = $r->tipoactividad;
        $fechainicio = $r->fechainicio;
        $fechatermino = $r->fechatermino;
        $horadeinicio = $r->horadeinicio;
        $horatermino = $r->horatermino;
        $detalleactividad = $r->detalleactividad;

        //condiciones para determinar si se subio algun archivo o no

        if ($r->file('archivos') != null) {

            $file = $r->file('archivos');
            $archivos = $file->getClientOriginalName();
            $archivos = date('Ymd_His_') . $archivos;
            \Storage::disk('local')->put($archivos, \File::get($file));

        } else {

            $archivos = 'Sin archivo';

        }

        if ($r->file('archivos2') != null) {

            $file2 = $r->file('archivos2');
            $archivos2 = $file2->getClientOriginalName();
            $archivos2 = date('Ymd_His_') . $archivos2;
            \Storage::disk('local')->put($archivos2, \File::get($file2));

        } else {

            $archivos2 = 'Sin archivo';
        }

        if ($r->file('archivos3') != null) {

            $file3 = $r->file('archivos3');
            $archivos3 = $file3->getClientOriginalName();
            $archivos3 = date('Ymd_His_') . $archivos3;
            \Storage::disk('local')->put($archivos3, \File::get($file3));

        } else {
            $archivos3 = 'Sin archivo';
        }

        //Condiciones si se lleno con algun link o no

        if ($r->link != null) {

            $link = $r->link;
        } else {
            $link = "Sin Link";
        }

        if ($r->link2 != null) {

            $link2 = $r->link2;
        } else {
            $link2 = "Sin Link";
        }

        if ($r->link3 != null) {

            $link3 = $r->link3;
        } else {
            $link3 = "Sin Link";
        }

        // Otras variables para recolectar lo que llegaron de los request
        $tipousuario = $r->tipousuario;
        $tipousuarioarea = $r->tipousuarioarea;
        $estado = $r->estado;
        $importancia = $r->importancia;
        $co = $r->co;

        //Condicion para insertar los registros dependiendo que tipo de usuario es
        /*
        Nota: La secretaria crea la actividad a nombre de su encargado, pero posteriormente el
        encargado tiene que autorizar la actividad
        */

        if(Auth()->user()->idtu_tipos_usuarios == 4){

            DB::Insert("INSERT INTO actividades (asunto, descripcion, fecha_creacion, turno, comunicado, fecha_inicio,
                        hora_inicio, fecha_fin, hora_fin, idtac_tipos_actividades, idar_areas, idu_users, status,
                        importancia, archivo1, archivo2, archivo3, link1, link2, link3, filtrocorreo)
                        VALUES ('$Asunto', '$detalleactividad', '$fechacreacion', '$turno', '$comunicado', '$fechainicio',
                        '$horadeinicio', '$fechatermino', '$horatermino', '$tipoactividad', '$idar_areas', '$idusuario', '$estado',
                        '$importancia', '$archivos', '$archivos2', '$archivos3', '$link', '$link2', '$link3', $co)");
        }else{

            DB::Insert("INSERT INTO actividades (asunto, descripcion, fecha_creacion, turno, comunicado, fecha_inicio,
                        hora_inicio, fecha_fin, hora_fin, idtac_tipos_actividades, idar_areas, idu_users, status,
                        importancia, archivo1, archivo2, archivo3, link1, link2, link3, aprobacion, filtrocorreo)
                        VALUES ('$Asunto', '$detalleactividad', '$fechacreacion', '$turno', '$comunicado', '$fechainicio',
                        '$horadeinicio', '$fechatermino', '$horatermino', '$tipoactividad', '$idar_areas', '$idusuario', '$estado',
                        '$importancia', '$archivos', '$archivos2', '$archivos3', '$link', '$link2', '$link3', 1, $co)");


        }

        //Variable que saca la ultima actividad (La actividad que se creo actualmente)
        $consul = DB::table('actividades')->max('idac');

        // For para almacenar todos los usuarios que se seleccionaron
        for ($i = 0; $i < count($tipousuarioarea); $i++) {

            DB::INSERT("INSERT INTO responsables_actividades (idu_users , idac_actividades) VALUES ('$tipousuarioarea[$i]','$consul')");
        }

        /**
         * Envio de correos a los usuarios asignados.
         * Obteniendo los responsables de el request
         */

        $userForMail = DB::select("SELECT CONCAT(us.titulo,' ', us.nombre, ' ', us.app, ' ', us.apm) AS nombre,
            CONCAT(ac.fecha_inicio,' al ', ac.fecha_fin) AS periodo, us.email,
            CONCAT(us2.titulo,' ', us2.nombre, ' ', us2.app, ' ', us2.apm) AS creador,
            ac.asunto, ac.comunicado, tp.nombre AS tipo
             FROM responsables_actividades AS res
             JOIN actividades AS ac ON ac.idac = res.idac_actividades
             JOIN tipos_actividades AS tp ON tp.idtac = ac.idtac_tipos_actividades
             JOIN users AS us ON us.idu = res.idu_users
             JOIN users AS us2 ON us2.idu = ac.idu_users
             WHERE  res.idac_actividades = $consul
        ");

        foreach($userForMail as $correos){
            Mail::to($correos->email)->send(new enviar_asignacion($correos));
        }
        //------------------------------------------------------

        //Condiciones que determinan que usuario es y donde lo va a redirigir
        if (Auth()->User()->idtu_tipos_usuarios == 3) {

            return redirect()->route('reporte_actividades');
        } else if (Auth()->User()->idtu_tipos_usuarios == 2) {

            return redirect()->route('actividades_creadas', ['id' => encrypt(Auth()->User()->idu)]);
        } else {

            return redirect('panel');
        }
    }

    public function actividades_modificacion($id)
    {
        //funcion para la vista de modificar actividad

        //Variable para desencriptar el id recibido
        $id = decrypt($id);

        //Consulta que recupera los datos de la actividad que se esta modificando

        $consul = DB::table('actividades')->where('idac', $id)
            ->join('users', 'users.idu', '=', 'actividades.idu_users')
            ->join('areas', 'areas.idar', '=', 'actividades.idar_areas')
            ->join('tipos_usuarios', 'tipos_usuarios.idtu', '=', 'users.idtu_tipos_usuarios')
            ->join('tipos_actividades', 'tipos_actividades.idtac', '=', 'actividades.idtac_tipos_actividades')
            ->select(
                'actividades.idac',
                'tipos_actividades.nombre as nombre_actividad',
                'actividades.asunto',
                'actividades.idtac_tipos_actividades',
                'actividades.descripcion',
                'actividades.fecha_creacion',
                'actividades.turno',
                'actividades.comunicado',
                'actividades.fecha_inicio',
                'actividades.fecha_fin',
                'actividades.hora_inicio',
                'actividades.hora_fin',
                'actividades.filtrocorreo',
                'areas.nombre as nombre_area',
                'tipos_usuarios.nombre as tipo_usuario',
                'users.titulo',
                'users.nombre',
                'users.app',
                'users.apm',
                'actividades.status',
                'actividades.importancia',
                'actividades.archivo1',
                DB::raw("SUBSTRING_INDEX(actividades.archivo1,'_',-1) AS archivo_redux"),
                'actividades.archivo2',
                DB::raw("SUBSTRING_INDEX(actividades.archivo2,'_',-1) AS archivo_redux2"),
                'actividades.archivo3',
                DB::raw("SUBSTRING_INDEX(actividades.archivo3,'_',-1) AS archivo_redux3"),
                'actividades.link1',
                'actividades.link2',
                'actividades.link3',
            )
            ->get();


        //Variable que saca a las personas que ya autorizaron y estan dando seguimiento a la actividad
        /*
        Nota:
        Esta variable va para la tabla del ZingGrid
        */
        $personas = DB::SELECT("SELECT CONCAT(us.titulo, ' ' , us.nombre, ' ', us.app, ' ', us.apm) AS nombre, ar.nombre AS nombre_area
                                FROM responsables_actividades AS re
                                INNER JOIN actividades AS ac ON ac.idac = re.idac_actividades
                                INNER JOIN users AS us ON us.idu = re.idu_users
                                INNER JOIN areas AS ar ON ar.idar = us.idar_areas
                                WHERE re.acuse = 1
                                AND re.idac_actividades = $id
                                ORDER BY ar.nombre ASC");

        //Variable para la creacion de un array
        $array = array();

        //Foreach para insertar las consultas dentro del array
        foreach ($personas as $personas) {

            array_push($array, array(
                "personas" => $personas->nombre,
                "areas" => $personas->nombre_area,
            ));
        }

        //variable para convertir el array en JSON
        $json = json_encode($array);


        //variable con consulta de las areas seleccionadas para el select
        $tipous = DB::SELECT("SELECT a.nombre, a.`idar`
        FROM actividades AS ac
        INNER JOIN responsables_actividades AS re ON re.idac_actividades = ac.idac
        INNER JOIN users AS u ON u.idu = re.idu_users
        INNER JOIN areas AS a ON a.idar = u.idar_areas
        WHERE ac.idac = $id
        GROUP BY a.nombre");


        //variable para creacion de un array
        $array2 = array();


        //foreach que guarda la consulta en un array
        foreach ($tipous as $t) {
            array_push($array2, $t->idar,);
        }


        /*
        Condicion que crea una variable en la cual se determina si en la consulta hay areas seleccionadas en las actividades seleccionadas
        la cual en esta consulta se sacan las otras areas menos las que se pusieron inicialmente en la creacion del evento
        por ello hay un not in que son las de la consulta anrerior
        */
        if (count($array2) > 0) {

            $no_seleccionar = DB::SELECT("SELECT *
                                FROM areas AS ar
                                WHERE ar.idar NOT IN (" . implode(',', $array2) . ")");

        } else {

            $no_seleccionar = DB::SELECT("SELECT *
            FROM areas AS ar");

        }



        // variable que saca a los usuarios de la actividad

        $users = DB::SELECT("SELECT u.idu, CONCAT(u.titulo, ' ' , u.app, ' ', u.apm, ' ' , u.nombre) AS usuario, u.email,
        a.idar, a.nombre AS nombre_area
        FROM actividades AS ac
        INNER JOIN responsables_actividades AS re ON re.idac_actividades = ac.idac
        INNER JOIN users AS u ON u.idu = re.idu_users
        INNER JOIN areas AS a ON a.idar = u.idar_areas
        WHERE ac.idac = $id");

        //Variables de tipo array
        $array3 = array();
        $array4 = array();


        //Foreach en la cual de la consulta se hace un push a los array creadas anteriormente
        foreach ($users as $us) {

            array_push($array3, $us->idu);
            array_push($array4, $us->idar);

        }

        /*
        Condicion en la cual se determina en los array si vienen con resultados, asi para
        tener un control sobre la consulta y poner los datos dentro del not in de la consulta
        con el fin de que muestre los usuarios del area apesar de que no esten seleccionados en la actividad
        */
        if (count($array3) > 0 && count($array4) > 0) {

            $no_seleccionar_user = DB::SELECT("SELECT us.idu, CONCAT(us.titulo, ' ' , us.app, ' ', us.apm, ' ' , us.nombre) AS usuario, us.email,
            ar.nombre AS nombre_area
            FROM users AS us
            INNER JOIN areas AS ar ON ar.idar = us.idar_areas
            WHERE us.idu NOT IN(" . implode(',', $array3) . ")
            AND ar.idar IN (" . implode(',', $array4) . ")
            AND us.idtu_tipos_usuarios NOT IN(1)");

        } else {

            $no_seleccionar_user = null;
        }

        //la variable consulta los tipos de actividades
        $tipo_actividad = DB::table('tipos_actividades')
            ->whereNotIn('idtac', [$consul[0]->idtac_tipos_actividades])
            ->orderBy('nombre', 'Asc')
            ->get();


        return view('Actividades.modificar_actividad')
            ->with('consul', $consul)
            ->with('tipo_actividad', $tipo_actividad)
            ->with('tipous', $tipous)
            ->with('users', $users)
            ->with('json', $json)
            ->with('no_seleccionar', $no_seleccionar)
            ->with('no_seleccionar_user', $no_seleccionar_user);
    }


    public function quitar_ajax(Request $request)
    {

        //Funcion de un AJAX para determinar si un usuario ya esta dando seguimiento a una actividad en caso de que se quiera remover

        //Variables con los valores enviandos en el request
        $val = $request->val;
        $id = $request->id;

        //Consulta que regresa los valores de las personas que estan dando seguimiento

        $consul = DB::SELECT("SELECT * FROM responsables_actividades AS re
                        WHERE re.idu_users = $val
                        AND re.idac_actividades = $id");


        return response()->json($consul);
    }

    public function quitar_ajax2(Request $request)
    {

        //Funcion de un AJAX para determinar si se quita un area y si algun usuario ya esta dando seguimiento, no se pueda quitar
        //Variables con los valores enviandos en el request

        $val = $request->val;
        $id = $request->id;

        //Consulta que trae a los usuario que ya tienen el acuse

        $consul = DB::SELECT("SELECT COUNT(re.acuse) AS contar FROM users AS us
        INNER JOIN responsables_actividades AS re ON re.idu_users = us.idu
        WHERE re.acuse = 1
        AND re.idac_actividades = $id
        AND us.idar_areas = $val");

        //Consulta en la que trae las areas

        $consul2 = DB::SELECT("SELECT us.idu, ar.nombre FROM users AS us
        INNER JOIN areas AS ar ON ar. idar = us.idar_areas
        WHERE ar.idar = $val");

        return response()->json([$consul, $consul2]);
    }

    public function update_actividades(Request $r)
    {
        //Funcion en la que se actualizan los datos de actividades

        //Variables que llegaron del request
        $id = $r->idac;
        $idusuario = $r->idusuario;
        $idar_areas = $r->idar_areas;
        $fechacreacion = $r->fechacreacion;
        $turno = $r->turno;
        $comunicado = $r->comunicado;
        $Asunto = $r->Asunto;
        $tipoactividad = $r->tipoactividad;
        $fechainicio = $r->fechainicio;
        $fechatermino = $r->fechatermino;
        $horadeinicio = $r->horadeinicio;
        $horatermino = $r->horatermino;
        $detalleactividad = $r->detalleactividad;

        //Condiciones para el almacenaje de archivos

        if (\Storage::disk('local')->exists($r->archivosoculto)) {

            $archivos = $r->archivosoculto;

        } elseif ($r->file('archivos') != null) {

            $file = $r->file('archivos');
            $archivos = $file->getClientOriginalName();
            $archivos = date('Ymd_His_') . $archivos;
            \Storage::disk('local')->put($archivos, \File::get($file));

        } else {

            $archivos = 'Sin archivo';
        }

        if (\Storage::disk('local')->exists($r->archivosoculto2)) {

            $archivos2 = $r->archivosoculto2;
        } else if ($r->file('archivos2') != null) {

            $file2 = $r->file('archivos2');
            $archivos2 = $file2->getClientOriginalName();
            $archivos2 = date('Ymd_His_') . $archivos2;
            \Storage::disk('local')->put($archivos, \File::get($file2));
        } else {

            $archivos2 = 'Sin archivo';
        }

        if (\Storage::disk('local')->exists($r->archivosoculto3)) {

            $archivos3 = $r->archivosoculto3;

        } else if ($r->file('archivos3') != null) {

            $file3 = $r->file('archivos3');
            $archivos3 = $file3->getClientOriginalName();
            $archivos3 = date('Ymd_His_') . $archivos3;
            \Storage::disk('local')->put($archivos3, \File::get($file3));

        } else {

            $archivos3 = 'Sin archivo';
        }

        if ($r->link != null) {

            $link = $r->link;

        } else {

            $link = "Sin Link";
        }

        if ($r->link2 != null) {

            $link2 = $r->link2;

        } else {

            $link2 = "Sin Link";
        }

        if ($r->link3 != null) {

            $link3 = $r->link3;

        } else {

            $link3 = "Sin Link";
        }

        //Otras variables que llegaron del request

        $tipousuario = $r->tipousuario;
        $tipousuarioarea = $r->tipousuarioarea;

        $estado = $r->estado;
        $importancia = $r->importancia;


        //Condicion que valida el tipo de usuario quien modifico la actividad

        if (Auth()->user()->idtu_tipos_usuarios != 4) {

            DB::UPDATE("UPDATE actividades SET asunto = '$Asunto', descripcion ='$detalleactividad', fecha_creacion = '$fechacreacion',
            turno = '$turno',  comunicado = '$comunicado', fecha_inicio = '$fechainicio',
            hora_inicio = '$horadeinicio', fecha_fin = '$fechatermino', hora_fin = '$horatermino', idtac_tipos_actividades = '$tipoactividad',
            status = '$estado',
            importancia = '$importancia',  archivo1 = '$archivos', archivo2 = '$archivos2', archivo3 = '$archivos3',
            link1 = '$link', link2 = '$link2', link3 = '$link3', aprobacion = 1
            WHERE idac = $id");

        } else {

            DB::UPDATE("UPDATE actividades SET asunto = '$Asunto', descripcion ='$detalleactividad', fecha_creacion = '$fechacreacion',
            turno = '$turno',  comunicado = '$comunicado', fecha_inicio = '$fechainicio',
            hora_inicio = '$horadeinicio', fecha_fin = '$fechatermino', hora_fin = '$horatermino', idtac_tipos_actividades = '$tipoactividad',
            status = '$estado',
            importancia = '$importancia',  archivo1 = '$archivos', archivo2 = '$archivos2', archivo3 = '$archivos3',
            link1 = '$link', link2 = '$link2', link3 = '$link3', aprobacion = 0
            WHERE idac = $id");
        }


        //Creacion de array
        $array = array();

        //variable que consulta a los usuarios que estan en la actividad
        $no_dentro = DB::SELECT("SELECT idu_users
        FROM responsables_actividades
        WHERE idac_actividades= $id");

        //se hace un push al array echa anteriormente
        foreach ($no_dentro as $no) {
            array_push($array, $no->idu_users);
        }

        //foreach para el array
        foreach ($array as $a) {

            /*
            En en este ciclo se verifica si los usuarios estan dentro o no de la actividad para borrarlos
            en caso de que se borrara
            */
            (in_array($a, $tipousuarioarea) ? "" : DB::DELETE("DELETE FROM responsables_actividades WHERE idu_users = $a AND idac_actividades = $id"));
        }

        //Ciclo para almacenar a los usuarios
        for ($i = 0; $i < count($tipousuarioarea); $i++) {

            //Se crea una consulta de las personas que estan en la actividad
            //Nota: No digan nada por el nombre de la variable, ya estaba cansado esa vez
            $prueba = DB::SELECT("SELECT idu_users
            FROM responsables_actividades
            WHERE idac_actividades= $id
            AND idu_users = $tipousuarioarea[$i]");

            //condicion el la cual  se determina si el usuario no existe en la actividad
            if (count($prueba) == 0) {
                DB::INSERT("INSERT INTO responsables_actividades(idu_users, idac_actividades) VALUES ($tipousuarioarea[$i] , $id)");
            }
        }


        //Condiciones que terminan donde redirigir
        if (Auth()->user()->idtu_tipos_usuarios == 2) {

            return redirect()->route('actividades_creadas', ['id' => encrypt(Auth()->user()->idu)]);
        } else if (Auth()->user()->idtu_tipos_usuarios == 3) {

            return redirect()->route('reporte_actividades');
        }
    }

    public function activacion($id, $activo)
    {
        //Funcion para activar o desactivar alguna actividad (Soft delete)

        //Variable que recupera el id y desencriptar
        $id = decrypt($id);
        //Variable que recupera que valor tiene de la base de datos
        $activo = decrypt($activo);

        //Condicion para activacion desactivacion

        if ($activo == 1) {

            DB::UPDATE("UPDATE actividades SET activo = '0' , status = 3 WHERE idac = $id");
        } else {
            DB::UPDATE("UPDATE actividades SET activo = '1', status = 1 WHERE idac = $id");
        }

        return redirect()->route('actividades_creadas', ['id' => encrypt(Auth()->User()->idu)]);
    }

    public function aprobacion($id, $aprobacion)
    {

        $id = decrypt($id);
        $aprobacion = decrypt($aprobacion);

        if ($aprobacion == 0) {

            DB::UPDATE("UPDATE actividades SET aprobacion = '1'  WHERE idac = $id");
        }

        return redirect()->route('actividades_pendientes', ['id' => encrypt(Auth()->User()->idu)]);
    }

    public function actividades_creadas($id)
    {

        //Funcion para traer la consulta de las actividades creadas


        $id_u = decrypt($id);
        $ar = Auth()->user()->idar_areas;
        $tipo = Auth::user()->idtu_tipos_usuarios;

        $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad, ac.aprobacion
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        $array = array();

        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1], '3' => $val[2]);
            } else {
                $arr = null;
            }
            return $arr;
        }

        if ($tipo != 4) {
            function btn($idac, $activo)
            {

                if ($activo == 1) {

                    return "<div class='btn-group me-2' role='group' aria-label='Second group'><a  class='btn btn-success btn-sm mt-1'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>
                    <a class='btn btn-danger mt-1 btn-sm' href=" . route('activacion', ['id' => encrypt($idac), 'activo' => encrypt($activo)]) . "><i class='nav-icon fas fa-ban'></i></a>
                    <a class='btn btn-warning mt-1 btn-sm' href=" . route('edit_modificacion', ['id' => encrypt($idac)]) . "><i class='fas fa-pencil-alt'></i></a><div>";

                } else {

                    return "<a class='btn btn-primary mt-1 btn-sm' href=" . route('activacion', ['id' => encrypt($idac), 'activo' => encrypt($activo)]) . "><i class='fas fa-check'></i></a>";

                }
            }
        } else {
            function btn($idac, $activo)
            {

                if ($activo == 1) {
                    return "<div class='btn-group me-2' role='group' aria-label='Second group'><a  class='btn btn-success btn-sm mt-1'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>";

                } else {

                    return "<div class='btn-group me-2' role='group' aria-label='Second group'><a class='btn btn-success btn-sm mt-1'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>";

                }
            }
        }

        function AB($data)
        {

            if (gettype($data) == "array") {

                return $data['1'][0] . " de " . $data['1'][1];
            } else {
                return 0;
            }
        }

        function C($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['2'], 0, '.', ' ') . '%';
            } else {
                return 0;
            }
        }

        function D($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['3'], 0, '.', ' ') . '%';
            } else {

                return 0;
            }
        }

        function E($status)
        {

            if ($status == 1) {
                return "En proceso";
            } elseif ($status == 2) {
                return "Concluido";
            } else {
                return "Cancelado";
            }
        }



        foreach ($ac_cre as $c) {

            $data = recorrer($c->porcentaje);

            array_push($array, array(
                'idac' => $c->idac,
                'turno' => $c->turno,
                'fecha_creacion' => Carbon::parse($c->fecha_creacion)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'asunto' => $c->asunto,
                'tipo_actividad' => $c->tipo_actividad,
                'descripcion' => $c->descripcion,
                'creador' => $c->creador,
                'periodo' => Carbon::parse($c->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [del] YYYY [al]') . Carbon::parse($c->fecha_fin)->locale('es')->isoFormat(' D [de] MMMM [del] YYYY'),
                'importancia' => $c->importancia,
                'nombre' => $c->nombre,
                'activo' => $c->activo,
                'acuse' => $c->acuse,
                'idu_users' => $c->idu_users,
                'avance' => AB($data),
                'atendido_por' =>  D($data),
                'estatus' =>  E($c->status),
                'operaciones' => btn($c->idac, $c->activo),
            ));
        }


        $json = json_encode($array);

        if (Auth()->user()->idtu_tipos_usuarios == 4) {

            $director = DB::SELECT("SELECT CONCAT(titulo, ' ',nombre, ' ',app, ' ',apm) AS nombre FROM users WHERE idtu_tipos_usuarios = 2 AND idar_areas = $ar");
            $dir = $director[0]->nombre;

            return view('Actividades.actividadescreadas', compact('json', 'dir'));

        } else {

            return view('Actividades.actividadescreadas', compact('json'));
        }
    }

    public function actividades_pendientes($id)
    {

        //Funcion para ver el reporte de las actividades que aun no se han aceptado para dar seguimiento

        $id_u = decrypt($id);
        $ar = Auth()->user()->idar_areas;
        $tipo = Auth::user()->idtu_tipos_usuarios;


        //Verificar el rol de usuario si es asistente o directivo
        if ($tipo == 4) {

            $dir = DB::SELECT("SELECT idu FROM users
                            WHERE idar_areas = " . Auth()->user()->idar_areas .
                            " AND idtu_tipos_usuarios = 2");

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad, ac.aprobacion
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = " . $dir[0]->idu . " AND ac.aprobacion = 0
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        } else {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad, ac.aprobacion
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 0
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }



        $array = array();

        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1], '3' => $val[2]);
            } else {
                $arr = null;
            }
            return $arr;
        }


        function btn($idac, $aprobacion, $activo, $tipo)
        {

            if ($aprobacion == 0 && $tipo != 4) {

                return "<div class='btn-group me-2' role='group' aria-label='Second group'><a class='btn btn-primary mt-1 btn-sm' href=" . route('aprobacion', ['id' => encrypt($idac), 'aprobacion' => encrypt($aprobacion)]) . "><i class='fas fa-check'></i></a>
                    <a class='btn btn-warning mt-1 btn-sm' href=" . route('edit_modificacion', ['id' => encrypt($idac)]) . "><i class='fas fa-pencil-alt'></i></a><div>";

            } else {

                return "<div class='btn-group me-2' role='group' aria-label='Second group'>
                    <a class='btn btn-warning mt-1 btn-sm' href=" . route('edit_modificacion', ['id' => encrypt($idac)]) . "><i class='fas fa-pencil-alt'></i></a><div>";

            }
        }


        function AB($data)
        {

            if (gettype($data) == "array") {

                return $data['1'][0] . " de " . $data['1'][1];

            } else {

                return 0;
            }
        }

        function C($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['2'], 0, '.', ' ') . '%';

            } else {

                return 0;
            }
        }

        function D($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['3'], 0, '.', ' ') . '%';

            } else {

                return 0;
            }
        }

        function E($status)
        {

            if ($status == 1) {

                return "En proceso";

            } elseif ($status == 2) {

                return "Concluido";

            } else {

                return "Cancelado";
            }
        }



        foreach ($ac_cre as $c) {

            $data = recorrer($c->porcentaje);

            array_push($array, array(
                'idac' => $c->idac,
                'turno' => $c->turno,
                'fecha_creacion' => Carbon::parse($c->fecha_creacion)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'asunto' => $c->asunto,
                'tipo_actividad' => $c->tipo_actividad,
                'descripcion' => $c->descripcion,
                'creador' => $c->creador,
                'periodo' => Carbon::parse($c->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [del] YYYY [al]') . Carbon::parse($c->fecha_fin)->locale('es')->isoFormat(' D [de] MMMM [del] YYYY'),
                'importancia' => $c->importancia,
                'nombre' => $c->nombre,
                'activo' => $c->activo,
                'acuse' => $c->acuse,
                'idu_users' => $c->idu_users,
                'avance' => AB($data),
                'atendido_por' =>  D($data),
                'estatus' =>  E($c->status),
                'operaciones' => btn($c->idac, $c->aprobacion, $c->activo, $tipo),
            ));
        }


        $json = json_encode($array);

        if (Auth()->user()->idtu_tipos_usuarios == 4) {

            $director = DB::SELECT("SELECT CONCAT(titulo, ' ',nombre, ' ',app, ' ',apm) AS nombre FROM users WHERE idtu_tipos_usuarios = 2 AND idar_areas = $ar");
            $dir = $director[0]->nombre;

            return view('Actividades.actividades_pendientes', compact('json', 'dir'));

        } else {

            return view('Actividades.actividades_pendientes', compact('json'));
        }
    }

    public function ajax_filtro_fecha(Request $request)
    {
        //Funcion para filtrar distintos datos

        $id_u = \Auth()->User()->idu;
        $fecha_orden = $request->fecha_orden;
        $fechaIni =  $request->fechaIni;
        $fechaFin =  $request->fechaFin;

        $ar = Auth()->user()->idar_areas;

        //Verificar el rol de usuario si es asistente o directivo
        if (Auth()->user()->idtu_tipos_usuarios == 4) {

            $dir = DB::SELECT("SELECT idu FROM users WHERE idar_areas = $ar AND idtu_tipos_usuarios = 2");
            $id = $dir[0]->idu;
            $id_u = $id;
        }


        if ($fecha_orden == 0) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }

        if ($fecha_orden == 1 && $fechaIni != NULL && $fechaFin != NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_inicio` BETWEEN  DATE('$fechaIni') AND DATE('$fechaFin')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }

        if ($fecha_orden == 1 && $fechaIni != NULL && $fechaFin == NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_inicio` >=  DATE('$fechaIni')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }

        if ($fecha_orden == 1 && $fechaIni == NULL && $fechaFin != NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_inicio` <=  DATE('$fechaFin')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }

        if ($fecha_orden == 2 && $fechaIni != NULL && $fechaFin != NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_fin` BETWEEN  DATE('$fechaIni') AND DATE('$fechaFin')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }

        if ($fecha_orden == 2 && $fechaIni != NULL && $fechaFin == NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_fin` >=  DATE('$fechaIni')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");
        }

        if ($fecha_orden == 2 && $fechaIni == NULL && $fechaFin != NULL) {

            $ac_cre = DB::SELECT("SELECT  ac.idac ,ac.turno, ac.fecha_creacion, ac.asunto ,CONCAT(us.titulo, ' ', us.nombre, ' ', us.app, ' ', us.apm) AS creador,
                ac.fecha_inicio,ac.fecha_fin, ac.importancia, ar.nombre, ac.activo, ra.acuse, ra.idu_users, ac.descripcion,
                porcentaje(ac.idac, $id_u) AS porcentaje, ac.status, ta.nombre AS tipo_actividad
                FROM actividades AS ac
                INNER JOIN users AS us ON us.idu = ac.idu_users
                INNER JOIN areas AS ar ON ar.idar = ac.idar_areas
                INNER JOIN tipos_actividades AS ta ON ta.idtac = ac.idtac_tipos_actividades
                LEFT JOIN responsables_actividades AS ra ON ra.idac_actividades = ac.idac
                LEFT JOIN seguimientos_actividades AS sa ON sa.idreac_responsables_actividades = idreac
                WHERE ac.idu_users = $id_u AND ac.aprobacion = 1
                AND ac.`fecha_fin` <=  DATE('$fechaFin')
                GROUP BY ac.idac
                ORDER BY ac.fecha_creacion DESC");

        }





        $array = array();

        function recorrer($value)
        {
            if (gettype($value) == "string") {
                $val = explode('*', $value);
                $arr = array('1' => explode('-', $val[0]), '2' => $val[1], '3' => $val[2]);
            } else {
                $arr = null;
            }
            return $arr;
        }

        function btn($idac, $activo)
        {
            //Verificar el rol de usuario si es asistente o directivo
            if (Auth()->user()->idtu_tipos_usuarios != 4) {

                if ($activo == 1) {

                    return "<div class='btn-group me-2' role='group' aria-label='Second group'><a  class='btn btn-success btn-sm mt-1'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>
                    <a class='btn btn-danger mt-1 btn-sm' href=" . route('activacion', ['id' => encrypt($idac), 'activo' => encrypt($activo)]) . "><i class='nav-icon fas fa-ban'></i></a>
                    <a class='btn btn-warning mt-1 btn-sm' href=" . route('edit_modificacion', ['id' => encrypt($idac)]) . "><i class='fas fa-pencil-alt'></i></a><div>";
                } else {

                    return "<a class='btn btn-primary mt-1 btn-sm' href=" . route('activacion', ['id' => encrypt($idac), 'activo' => encrypt($activo)]) . "><i class='fas fa-check'></i></a>";
                }
            } else {

                if ($activo == 1) {

                    return "<div class='btn-group me-2' role='group' aria-label='Second group'><a  class='btn btn-success btn-sm mt-1'  href=" . route('Detalles', ['id' => encrypt($idac)]) . "><i class='nav-icon fas fa-eye'></i></a>";

                }
            }
        }

        function AB($data)
        {

            if (gettype($data) == "array") {

                return $data['1'][0] . " de " . $data['1'][1];
            } else {
                return 0;
            }
        }

        function C($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['2'], 0, '.', ' ') . '%';
            } else {
                return 0;
            }
        }

        function D($data)
        {

            if (gettype($data) == "array") {

                return number_format($data['3'], 0, '.', ' ') . '%';
            } else {

                return 0;
            }
        }

        function E($status)
        {

            if ($status == 1) {
                return "En proceso";
            } elseif ($status == 2) {
                return "Concluido";
            } else {
                return "Cancelado";
            }
        }



        foreach ($ac_cre as $c) {

            $data = recorrer($c->porcentaje);

            array_push($array, array(
                'idac' => $c->idac,
                'turno' => $c->turno,
                'fecha_creacion' => Carbon::parse($c->fecha_creacion)->locale('es')->isoFormat('D [de] MMMM [del] YYYY'),
                'asunto' => $c->asunto,
                'tipo_actividad' => $c->tipo_actividad,
                'descripcion' => $c->descripcion,
                'creador' => $c->creador,
                'periodo' => Carbon::parse($c->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM [del] YYYY [al]') . Carbon::parse($c->fecha_fin)->locale('es')->isoFormat(' D [de] MMMM [del] YYYY'),
                'importancia' => $c->importancia,
                'nombre' => $c->nombre,
                'activo' => $c->activo,
                'acuse' => $c->acuse,
                'idu_users' => $c->idu_users,
                'avance' => AB($data),
                'atendido_por' =>  D($data),
                'estatus' =>  E($c->status),
                'operaciones' => btn($c->idac, $c->activo),
            ));
        }


        $json = json_encode($array);

        return response()->json($json);
    }

    //Agregar otro tipo de activideades. Nota en la vista esta comentada debido a que no se ha autorizado del todo esta modificacion
    public function add_tipo_actidad(Request $r){
        $nombre = $r->nombre;
        //return $r;
        $t_actividades = TiposActividades::create($r->all());
        //$data = json_encode($t_actividades);
        return response()->json($t_actividades);
        //return $nombre; //response()->json($t_actividades);
    }
}
