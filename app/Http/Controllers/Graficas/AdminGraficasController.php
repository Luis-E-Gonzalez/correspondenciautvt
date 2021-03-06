<?php

namespace App\Http\Controllers\Graficas;

use App\Http\Controllers\Controller;
use App\Models\Areas;
use App\Models\ResponsablesActividades;
use App\Models\SeguimientosActividades;
use App\Models\TiposActividades;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AdminGraficasController extends Controller
{
    public function seguimiento($idac){
        return redirect()->route('detallesSeguimiento',[ 'idac' => encrypt($idac) ]);
    }
    public function dashboard()
    {
        $tiposActividades = ResponsablesActividades::join('actividades','actividades.idac','responsables_actividades.idac_actividades')
            ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
            ->where('actividades.aprobacion', 1)
            ->select('tipos_actividades.idtac', 'tipos_actividades.nombre')
            ->groupBy('tipos_actividades.nombre')
            ->get();
        $areas = ResponsablesActividades::join('actividades','actividades.idac','responsables_actividades.idac_actividades')
            ->join('areas','areas.idar','actividades.idar_areas')
            ->select('areas.idar', 'areas.nombre')
            ->groupBy('areas.idar')
            ->get();
        return view('sistema.graficas.admin-dashboard',[
            'tipo_actividades' => $tiposActividades,
            'areas' => $areas
        ]);
    }

    public function getEstadisticasDeActividades(Request $request)
    {
      //Fecha inicio y Fecha fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividadesCompletadas = $this->getActividadesCompletadas($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesEnProceso = $this->getActividadesEnProceso($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesSinEntregar = $this->getActividadesSinEntregar($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesConAcuseDeRecibido = $this->getActividadesConAcuseDeRecibido($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesSinAcuseDeRecibido = $this->getActividadesSinAcuseDeRecibido($tipoActividad,$inicio,$fin,$request->areas)->count();

                $tipoActividad->actividadesCompletadasEnTiempo = $this->getActividadesCompletadasEnTiempo($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesCompletadasFueraDeTiempo = $this->getActividadesCompletadasFueraDeTiempo($tipoActividad,$inicio,$fin,$request->areas)->count();

                $tipoActividad->actividadesEnProcesoEnTiempo = $this->getActividadesEnProcesoEnTiempo($tipoActividad,$inicio,$fin,$request->areas)->count();
                $tipoActividad->actividadesEnProcesoFueraDeTiempo = $this->getActividadesEnProcesoFueraDeTiempo($tipoActividad,$inicio,$fin,$request->areas)->count();

                $tipoActividad->actividadesTotales = $tipoActividad->actividadesCompletadas +
                                                    $tipoActividad->actividadesEnProceso +
                                                    $tipoActividad->actividadesSinEntregar;


                return $tipoActividad;
            });

        return $tiposActividades;
    }
    public function actividadesCompletadas(Request $request)
    {
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesCompletadas($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProceso(Request $request)
    {
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesEnProceso($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesSinEntregar(Request $request)
    {
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesSinEntregar($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesConAcuseDeRecibido(Request $request)
    {
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesConAcuseDeRecibido($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesSinAcuseDeRecibido(Request $request)
    {
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesSinAcuseDeRecibido($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesCompletadasEnTiempo(Request $request){
        //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        return TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesCompletadasEnTiempo($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
    }

    public function actividadesCompletadasFueraDeTiempo(Request $request){
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($request){
                $tipoActividad->actividades = $this->getActividadesCompletadasFueraDeTiempo($tipoActividad,$request->inicio,$request->fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProcesoEnTiempo( Request $request){
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesEnProcesoEnTiempo($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProcesoFueraDeTiempo(Request $request){
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($inicio,$fin,$request){
                $tipoActividad->actividades = $this->getActividadesEnProcesoFueraDeTiempo($tipoActividad,$inicio,$fin,$request->areas);
                return $tipoActividad;
            });
        return $tiposActividades;
    }


    public function getActividades(Request $request)
    {
        return  TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($request){
                $tipoActividad->actividades_completadas = $this->getActividadesCompletadas($tipoActividad,$request->inicio,$request->fin,$request->areas);
                $tipoActividad->actividades_en_proceso = $this->getActividadesEnProceso($tipoActividad,$request->inicio,$request->fin,$request->areas);
                $tipoActividad->actividades_sin_entregar = $this->getActividadesSinEntregar($tipoActividad,$request->inicio,$request->fin,$request->areas);
                $tipoActividad->actividades_con_acuse_de_recibido = $this->getActividadesConAcuseDeRecibido($tipoActividad,$request->inicio,$request->fin,$request->areas);
                $tipoActividad->actividades_sin_acuse_de_recibido = $this->getActividadesSinAcuseDeRecibido($tipoActividad,$request->inicio,$request->fin,$request->areas);
                return $tipoActividad;
            });
    }

    public function getActividadesCompletadas(TiposActividades $tiposActividades, $inicio, $fin,$areas){
      //Fecha Inicial y Final
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        $actividades = ResponsablesActividades::join('seguimientos_actividades',
                'seguimientos_actividades.idreac_responsables_actividades',
                'responsables_actividades.idreac'
            )
            ->where('seguimientos_actividades.porcentaje', 100)
            ->groupBy('responsables_actividades.idreac')
            ->select('responsables_actividades.idreac')
            ->get();
        if($actividades->count() < 1 ) return $actividades;
        return User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha','!=', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $actividades)
        ->where('actividades.idar_areas',$areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->last()->porcentaje;//$seguimiento->avg('porcentaje');
            $collection->ultimo_seguimiento = $seguimiento->last();
            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesEnProceso(TiposActividades $tiposActividades, $inicio, $fin,$areas){
      //Fecha Inicial y Final
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
      //Obtener el porcentaje del ultimo seguimiento de la actividad
        $query = "SELECT t1.idreac
                FROM
                (SELECT idreac_responsables_actividades AS idreac ,ultimoporcentaje( idreac_responsables_actividades) AS ultimoporcentaje
                FROM seguimientos_actividades
                GROUP BY idreac_responsables_actividades) AS t1
                WHERE t1.ultimoporcentaje <100";
        $actividades = DB::select($query);

        if(count($actividades)<1) return  collect([]);
      //creamos un array para asignar solo los id de los responsables de actividades
      //para posteriormente colodarlos en un where in
        $Actividades = [];
        foreach($actividades AS $actividad){
            array_push($Actividades,$actividad->idreac);
        }
        return User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $Actividades)
        ->where('actividades.idar_areas',$areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
          //obtenemos el promedio/porcentaje global del segimiento de las actividades que se les han dado
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesSinEntregar(TiposActividades $tiposActividades, $inicio, $fin,$areas){
      //Fecha Inicial y Final
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        $actividades = ResponsablesActividades::select('idreac')
            ->groupBy('responsables_actividades.idreac')
            ->get()
            ->each(function($responsableActividad){
                $responsableActividad->seguimiento =SeguimientosActividades::where('idreac_responsables_actividades',$responsableActividad->idreac)
                    ->groupBy('idreac_responsables_actividades')
                    ->get();
                return $responsableActividad;

            });
      //Eliinamos las actividades que ya tengan seguimiento
            foreach($actividades AS $key => $actividad){
                if($actividad->seguimiento->count()>0){
                    $actividades->forget($key);
                }
              //eliminamos la propiedad seguimiento del arreglo de actividade ya que no se reruiere
                unset($actividad['seguimiento']);
            }

        //return $actividades;
        if($actividades->count() < 1 ) {return $actividades;}
        return User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $actividades)
        ->where('actividades.idar_areas',$areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
          //obtenemos el promedio/porcentaje global del segimiento de las actividades que se les han dado
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesConAcuseDeRecibido(TiposActividades $tiposActividades, $inicio, $fin,$areas){
      //Fecha Inicial y Final
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        return User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        ->where('responsables_actividades.firma','!=', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.idar_areas',$areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
          //obtenemos el promedio/porcentaje global del segimiento de las actividades que se les han dado
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesSinAcuseDeRecibido(TiposActividades $tiposActividades, $inicio, $fin,$areas){
      //Fecha inicio y Fin
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        return User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        ->where('responsables_actividades.firma', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.idar_areas',$areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
          //obtenemos el promedio/porcentaje global del segimiento de las actividades que se les han dado
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesPorTipoArea(Request $request){
      //Fecha Inicial y Final
        $inicio = $request->inicio;
        $fin = $request->fin;
     //formatea la fecha
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        
      return [['actividades' => User::join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('tipos_actividades.nombre', $request->tipo_area)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.idar_areas',$request->areas)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
          //obtenemos el promedio/porcentaje global del segimiento de las actividades que se les han dado
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        })]];
    }

    private function getActividadesCompletadasEnTiempo(TiposActividades $tiposActividades, $inicio, $fin,$areas){
        $actividadesCompletadas = $this->getActividadesCompletadas($tiposActividades,$inicio,$fin,$areas);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->ultimo_seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');
          //se eliminan las actividades las cuales exceden la fecha de termino
            if($seguimiento > $fechaFin){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesCompletadasFueraDeTiempo(TiposActividades $tiposActividades, $inicio, $fin,$areas){

        $actividadesCompletadas = $this->getActividadesCompletadas($tiposActividades,$inicio,$fin,$areas);

        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->ultimo_seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');
          //Eliminamos las actividades que fueron completadas fuera de tiempo
            if(!($seguimiento > $fechaFin)){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesEnProcesoEnTiempo(TiposActividades $tiposActividades, $inicio, $fin,$areas){
        $actividadesCompletadas = $this->getActividadesEnProceso($tiposActividades,$inicio,$fin,$areas);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
          //fecha seguimiento cuando fue creado
            $seguimiento = new Carbon($actividadCompletada->seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');
          //se eliminan las actividades las cuales la fehca de seguimiento ha excedido la fecha limite de entrega
            if($seguimiento > $fechaFin){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesEnProcesoFueraDeTiempo(TiposActividades $tiposActividades, $inicio, $fin,$areas){
        $actividadesCompletadas = $this->getActividadesEnProceso($tiposActividades,$inicio,$fin,$areas);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
           //fecha seguimiento cuando fue creado
            $seguimiento = new Carbon($actividadCompletada->seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');
          //se eliminan las actividades las cuales la fehca de seguimiento no ha excedido la fecha limite de entrega
            if(!($seguimiento > $fechaFin)){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

}
