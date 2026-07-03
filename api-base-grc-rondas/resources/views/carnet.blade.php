<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: 8.44cm 14.5cm;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
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

        .foto-placeholder {
            width: 130px;
            height: 160px;
            border: 5px solid #1a2a6c;
            background-color: #f0f0f0;
            line-height: 160px;
            text-align: center;
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
                        <img src="file://{{ public_path('images/feroca_logo.png') }}" style="width: 1.7cm; height: 1.7cm;" alt="">
                    </td>
                    <td style="width: 60%; text-align: center;">
                        <strong class="titulo-feroca">FEDERACIÓN REGIONAL DE <br> RONDAS CAMPESINAS <br> DE CAJAMARCA</strong>
                        <br>
                        <small class="subtitulo-feroca">LEY N° 27908 - D.S 025 - REG. N° 027</small>
                    </td>
                    <td style="width: 20%; text-align: right;">
                        <img src="file://{{ public_path('images/cunarc_logo.png') }}" style="width: 1.7cm; height: 1.7cm;" alt="">
                    </td>
                </tr>
            </table>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="contenido-info">
            <table style="background-color: white; width: 100%; border-collapse: collapse;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 10px;">
                        @if(!empty($fotoPath))
                            <img src="{{ $fotoPath }}" style="width: 130px; height: 160px; border: 5px solid #1a2a6c;" alt="Foto"/>
                        @else
                            <div class="foto-placeholder">
                                SIN FOTO
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 12px; padding-bottom: 6px;">
                        <strong>{{ $apellido_paterno ?? '' }} {{ $apellido_materno ?? '' }}</strong>
                        <br>
                        <strong>{{ $nombres ?? '' }}</strong>
                        <br>
                        <strong style="font-size: 12px">DNI: {{ $numero ?? '' }}</strong>
                        <br>
                        <strong style="font-size: 10px">Cargo: {{ $cargo ?? 'Rondero' }}</strong>
                        <br>
                        <strong style="font-size: 10px">Base: {{ $base ?? '' }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center; padding-left: 25px; font-size: 10px; padding-top: 10px; padding-bottom: 10px;">
                        En caso de encontrarlo sírvase entregarlo a una base de las rondas campesinas.
                    </td>
                    <td style="text-align: center;">
                        @if(!empty($qrCodePath))
                            <img src="{{ $qrCodePath }}" style="width: 80px; height: 80px;" alt="QR"/>
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
