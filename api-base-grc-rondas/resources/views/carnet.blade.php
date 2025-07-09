<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: 8.44cm 13.15cm; /* Ajusta el tamaño del carnet */
            margin: 0; /* Sin márgenes para aprovechar todo el espacio */

        }
        body {
            font-family: 'Arial, sans-serif';
            font-size: 15px;
            background-color: #21b14f;
            z-index: 1;
        }

        .watermark_a {
            position: absolute;
            top: 18%;
            left: 0;
            width: 100%;
            vertical-align: center;
            opacity: 0.1; /* Ajusta la transparencia */
            background-color: white
        }
        td.container > div {
            /*width: 100%;*/
            /*height: 100%;*/
            overflow:hidden;
        }
        td.container {
            height: 105px;
        }
    </style>
</head>
<body>

<div class="carnet">
    <div class="cara_a">
        <table style="padding: 5px">
            <tr>
                <td><img src="{{ public_path('images/feroca_logo.png') }}" style="width: 1cm; height: 1cm;" alt=""></td>
                <td style="text-align: center;"><strong>FEDERACIÓN REGIONAL DE RONDAS CAMPESINAS DE CAJAMARCA</strong><BR><SMALL style="font-size: 11px;">LEY N° 27908 - D.S 025 - REG. N° 027</SMALL></td>
                <td><img src="{{ public_path('images/cunarc_logo.png') }}" style="width: 1cm; height: 1cm;" alt=""></td>
            </tr>
        </table>
        <table style="z-index: 3; background-color: white">
            <tr>
                <td colspan="3" style="text-align: center;">
                    <div ><img class="watermark_a" src="{{ public_path('images/cunarc_logo.png') }}"/></div>
                    <img src="data:image/png;base64,{{ $foto }}" style="width: 130px; height: 160px; border: 5px solid brown; "/>
{{--                    {{ asset('files_rondas/fotos_personas/' . $foto) }}--}}
                </td>
            </tr>
            <tr>
                <td class="container" colspan="3" style="text-align: center;">
                    <div>
                        <strong>{{ $apellido_paterno }} {{ $apellido_materno }}</strong>
                        <br>
                        <strong>{{ $nombres }}</strong>
                        <br>
                        <strong style="font-size: 12px">DNI: {{ $numero }}</strong>
                        <br>
                        <strong style="font-size: 10px">
                            @forelse($cargos as $cargo)
                                {{ $cargo }}
                                @unless($loop->last)
                                    <br>
                                @endunless
                            @empty
                                RONDERO
                            @endforelse
                        </strong>
                        <br>
                        <strong style="font-size: 10px">Base: {{$base}}</strong>
                    </div>

                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-size: 10px;">
                    En caso de encontrarlo sírvase entregarlo a una base de las rondas campesinas.
                </td>
                <td style="text-align: center;">
                    <img src="{{ $qrCodePath }}" style="width: 80px; height: 80px;"/>
                </td>
            </tr>
        </table>
        <p style="font-size: 10px; text-align: center; vertical-align: center;">{{$region}}-{{$provincia}}-{{$distrito}}</p>
    </div>
</div>
</body>
</html>
