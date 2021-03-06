@extends('layout.layout')
@section('content')
@section('header')

<script src='{{asset('src/js/zinggrid.min.js')}}'></script>
<script src='{{asset('src/js/zinggrid-es.js')}}'></script>

<!-- Libreria para usar xlsx en js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.13.1/xlsx.full.min.js"></script>
<script src="{{ asset('src/js/xlsx.js') }}"></script>

<script>
  if (es) ZingGrid.registerLanguage(es, 'custom');
</script>

@endsection
<div class="card">
  <div class="card-header">
    <div class="row">
      <div class="col-sm-11">
        <h3>Reporte de actividades / Oficios</h3>
      </div>
      <div class="col-sm-1">
        <a href="{{route('create_actividades')}}"><button class="btn btn-primary">Nuevo</button></a>
      </div>
    </div>

    <div class="text-center">
      <button id="btn_exportar_excel" type="button" class="btn btn-success">
        Exportar a EXCEL
      </button>
    </div>

    <div class="row">
      <div class="col-sm-4">
        <label for="">Fecha orden:</label>
      </div>
      <div class="col-sm-4">
        <label for="">Fecha Inicio:</label>
      </div>

      <div class="col-sm-4">
        <label for="">Fecha Fin:</label>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <select class="form-control" name="fecha_orden" id="fecha_orden">
          <option value="0">Todos los registros</option>
          <option value="1">Fecha inicio</option>
          <option value="2">Fecha fin</option>
        </select>
        <button type="button" class="btn btn-primary mt-1" id="button">Enviar</button> <button type="button" class="btn btn-primary mt-1" id="limpiar">Limpiar</button>
      </div>
      <div class="col-sm-4">
        <input class="form-control" name="fechaIni" id="fechaIni" type="date" readonly>

      </div>
      <div class="col-sm-4">
        <input class="form-control" name="fechaFin" id="fechaFin" type="date" readonly>
      </div>
    </div>
  </div>
  <div class="card-body">
    @if (Session::has('message'))
    <p class="alert alert-info">
      {{Session::get('message')}}
    </p>
    @endif
    @if (Session::has('message2'))
    <p class="alert alert-danger">
      {{Session::get('message2')}}
    </p>
    @endif
    @if (Session::has('message3'))
    <p class="alert alert-warning">
      {{Session::get('message3')}}
    </p>
    @endif
    <zing-grid lang="custom" caption='Reporte de oficios' sort search pager page-size='10' page-size-options='1,2,3,4,5,10' layout='row' viewport-stop theme='android' id='zing-grid' filter selector data="{{$json}}">
      <zg-colgroup>
        <zg-column index='turno' header='Turno' width="100" type='number'></zg-column>
        <zg-column index='asunto' header='Asunto' width="200" type='text'></zg-column>
        <zg-column index='nombre_actividad' header='Tipo actividad' width="200" type='text'></zg-column>
        <zg-column index='descripcion' header='Descripci??n' width='200'></zg-column>
        <zg-column index='fecha_creacion' header='Fecha creaci??n' width="200" type='text'></zg-column>
        <zg-column index='creador' header='Creador' width="200" type='text'></zg-column>
        <zg-column index='periodo' header='Periodo' width="220" type='text'></zg-column>
        <zg-column index='importancia' header='Importancia' width="130" type='text'></zg-column>
        <zg-column index='nombre' header='??rea responsable' width="170" type='text'></zg-column>
        <zg-column index='avance' header='Avance' width="120" type='text'></zg-column>
        <zg-column index='atendido_por' header='Atendido por' width="135" type='text'></zg-column>
        <zg-column index='estatus' header='Estado' width="120" type='text'></zg-column>
        <zg-column align="center" filter="disabled" index='operaciones' header='Operaciones' width="150" type='text'></zg-column>
      </zg-colgroup>
    </zing-grid>
  </div>
</div>
<script>
//funcion para filtrar los resultados mediante ajax
  $('#button').on("click", function() {

    let fecha_orden = $('#fecha_orden').val()
    let fechaIni = $('#fechaIni').val()
    let fechaFin = $('#fechaFin').val()
    $.ajax({
      type: "GET",
      url: "{{route('fecha_ajax')}}",
      data: {
        fecha_orden: fecha_orden,
        fechaIni: fechaIni,
        fechaFin: fechaFin
      },
      success: function(data) {
        console.log(data);
        $('#zing-grid').removeAttr('data');
        $('#zing-grid').attr("data", data);
      }
    })

  })
  //boton para limpiar los campos del filtro
  $('#limpiar').on("click", function() {
    $("#fechaIni").val("");
    $("#fechaFin").val("");
    $("#fecha_orden").val(0);
    $('#fechaIni').attr("readOnly", true);
    $('#fechaFin').attr("readOnly", true);
    $('#fechaIni').val("");
    $('#fechaFin').val("");
  })
  $('#fecha_orden').on("change", function() {
    if ($(this).val() == 0) {
      $('#fechaIni').attr("readOnly", true);
      $('#fechaFin').attr("readOnly", true);
      $('#fechaIni').val("");
      $('#fechaFin').val("");
    } else {
      $('#fechaIni').removeAttr("readOnly");
      $('#fechaFin').removeAttr("readOnly");
    }
  })
</script>
@endsection



<!-- E x c e l -->

@section('scripts')
    <script>
        $( document ).ready( () => {

            const excel = () => {

                let date = new Date(), sheet, data, columns, rows, zing_grid = document.querySelector( 'zing-grid' );

                const headers = [ "A3", "B3", "C3", "D3", "E3", "F3", "G3", "H3", "I3", "J3", "K3", "L3"];

                data = zing_grid.getData({
                    headers:true,
                    cols:'visible',
                    rows:'visible',
                });

                sheet = XLSX.utils.aoa_to_sheet([
                    ["Reporte de actividades"],
                ]);

                XLSX.utils.sheet_add_aoa( sheet, [
                    [`Fecha de reporte: ${ date.toLocaleDateString() } ${ date.getHours() }:${ date.getMinutes() }`],
                ], { origin: -1 } );

                XLSX.utils.sheet_add_aoa( sheet, [
                   ["Turno",
                   "Asunto",
                   "Tipo de Actividades",
                   "Descripci??n",
                   "Fecha de Creaci??n",
                   "Creador",
                   "Periodo",
                   "Importancia",
                   "??rea",
                   "Avance",
                   "Atendido",
                   "Estado"],
                ], { origin: -1 } );

                for ( value of data )
                {
                    XLSX.utils.sheet_add_aoa( sheet, [
                        [ value.turno,
                          value.asunto,
                          value.nombre_actividad,
                          value.descripcion,
                          value.fecha_creacion,
                          value.creador,
                          value.periodo,
                          value.importancia,
                          value.nombre,
                          value.avance,
                          value.atendido_por,
                          value.estatus
                        ],
                    ], { origin: -1 } );
                }

                // Size columns
                columns = [
                        {wch:20}, // turno
                        {wch:40}, // asunto
                        {wch:25}, // tipo de actividad
                        {wch:40}, // descripci??n
                        {wch:20}, // fecha de creaci??n
                        {wch:30}, // creadi por (creador)
                        {wch:30}, // periodo
                        {wch:20}, // importancia
                        {wch:30}, // ??rea
                        {wch:20}, // porcentaje
                        {wch:20}, // Atendido
                        {wch:30}, // estado
                    ];

                sheet['!cols'] = columns;

                sheet["!rows"] = rows;

                let mergeA1K1 = { s: {r:0, c:0}, e: {r:0, c:11} }; // Merge A1:K1

                let mergeA2K2 = { s: {r:1, c:0}, e: {r:1, c:11} }; // Merge A2:K2

                if( ! sheet['!merges'] ) sheet['!merges'] = [];

                sheet['!merges'].push( mergeA1K1 );

                sheet['!merges'].push( mergeA2K2 );

                // set the style of target cell
                sheet["A1"].s = {
                    font: {
                        name: 'Arial',
                        sz: 18,
                        bold: true,
                        color: { rgb: "00000000" }
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                };

                sheet["A2"].s = {
                    font: {
                        name: 'Arial',
                        sz: 14,
                        bold: false,
                        color: { rgb: "00000000" }
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                };

                for( value of headers )
                {

                  sheet[ value ].s = {
                    fill :{
                        patternType : 'solid',
                        fgColor: { rgb: "43B105" },
                        bgColor: { rgb: "43B105" },
                    },
                    font: {
                        name: 'Arial',
                        sz: 12,
                        bold: false,
                        color: { rgb: "FFFFFFFF" },
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                  };

                }

                let book = XLSX.utils.book_new();

                XLSX.utils.book_append_sheet( book, sheet, 'Hoja 1' );

                XLSX.writeFile( book, 'Reporte_de_Actividades.xlsx' );

            }

            $( '#btn_exportar_excel' ).on( 'click', () => {

                excel();

            } );

  });
</script>
@endsection
