<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: 8.44cm 14.5cm;
            margin: 0;
        }
        body {
            font-family: 'Arial, sans-serif';
            font-size: 15px;
            background-color: #315fbf;
            margin: 0;
            padding: 0;
        }

        .carnet {
            width: 100%;
            height: 100%;
        }

        /* Degradado azul marino a azul normal para header */
        .header-gradient {
            background: linear-gradient(135deg, #0a1628, #1a3a6c, #2a5a8a);
            padding: 8px 5px;
            text-align: center;
            color: white;
        }
        
        /* Degradado azul marino a azul normal para footer */
        .footer-gradient {
            background: linear-gradient(135deg, #0a1628, #1a3a6c, #2a5a8a);
            padding: 8px 5px;
            text-align: center;
            margin-top: 10px;
        }

        .watermark_a {
            position: absolute;
            top: 18%;
            left: 0;
            width: 100%;
            vertical-align: center;
            opacity: 0.1;
            background-color: white
        }
        
        td.container > div {
            overflow: hidden;
        }
        
        td.container {
            height: 105px;
        }
        
        .foto-placeholder {
            width: 130px;
            height: 160px;
            border: 5px solid #1a2a6c;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 12px;
            margin: 0 auto;
        }
        
        .footer-text {
            font-size: 12px;
            text-align: center;
            color: white;
            margin: 0;
            font-weight: bold;
        }
        
        .ubicacion-text {
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
            margin-bottom: 3px;
            font-weight: bold;
            color: white;
            line-height: 1.2;
        }
        
        /* Título más grande */
        .titulo-feroca {
            font-size: 11px;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .subtitulo-feroca {
            font-size: 9px;
        }
        
        /* Fondo blanco solo para el contenido de información */
        .contenido-info {
            background-color: white;
            margin: 5px;
            border-radius: 5px;
            padding: 5px;
        }
        
        /* Footer con menos espacio */
        .footer-gradient {
            padding: 6px 5px;
        }
    </style>
</head>
<body>

<div class="carnet">
    <div class="cara_a">
        <!-- HEADER CON DEGRADADO AZUL MARINO A AZUL NORMAL -->
        <div class="header-gradient">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 20%; text-align: left;">
                        <img src="{{ public_path('images/feroca_logo.png') }}" style="width: 0.9cm; height: 0.9cm;" alt="">
                    </td>
                    <td style="width: 60%; text-align: center;">
                        <strong class="titulo-feroca">FEDERACIÓN REGIONAL DE RONDAS<br>CAMPESINAS DE CAJAMARCA</strong>
                        <br>
                        <small class="subtitulo-feroca">LEY N° 27908 - D.S 025 - REG. N° 027</small>
                    </td>
                    <td style="width: 20%; text-align: right;">
                        <img src="{{ public_path('images/cunarc_logo.png') }}" style="width: 0.9cm; height: 0.9cm;" alt="">
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- CONTENIDO PRINCIPAL -->
        <div class="contenido-info">
            <table style="z-index: 3; background-color: white; width: 100%;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 10px;">
                        <div><img class="watermark_a" src="{{ public_path('images/cunarc_logo.png') }}"/></div>
                        
                        @if(!empty($foto))
                            <img src="data:image/jpeg;base64,{{ $foto }}" style="width: 130px; height: 160px; border: 5px solid #1a2a6c; object-fit: cover;"/>
                        @else
                            <div class="foto-placeholder">
                                SIN FOTO
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="container" colspan="3" style="text-align: center;">
                        <div>
                            <strong>{{ $apellido_paterno ?? '' }} {{ $apellido_materno ?? '' }}</strong>
                            <br>
                            <strong>{{ $nombres ?? '' }}</strong>
                            <br>
                            <strong style="font-size: 12px">DNI: {{ $numero ?? '' }}</strong>
                            <br>
                            <strong style="font-size: 10px">
                                @forelse($cargos ?? [] as $cargo)
                                    {{ $cargo }}
                                    @unless($loop->last)
                                        <br>
                                    @endunless
                                @empty
                                    RONDERO
                                @endforelse
                            </strong>
                            <br>
                            <strong style="font-size: 10px">Base: {{ $base ?? '' }}</strong>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center; padding-left: 25px; font-size: 10px; padding-top: 10px; padding-bottom: 10px;">
                        En caso de encontrarlo sírvase entregarlo a una base de las rondas campesinas.
                    </td>
                    <td style="text-align: center;">
                        @if(!empty($qrCodePath))
                            <img src="{{ $qrCodePath }}" style="width: 80px; height: 80px;"/>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- UBICACIÓN - SIN ESPACIO INTERLINEADO -->
        <p class="ubicacion-text">
            {{ $region ?? '' }}-{{ $provincia ?? '' }}-{{ $distrito ?? '' }}
        </p>
        
        <!-- FOOTER CON DEGRADADO AZUL MARINO A AZUL NORMAL -->
        <div class="footer-gradient">
            <p class="footer-text">
                RONDERO ACREDITADO
            </p>
        </div>
    </div>
</div>
</body>
</html>