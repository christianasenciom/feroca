<table>
    <thead>
        <tr>
            <th colspan="5">DENUNCIAS DE {{ $persona }}</th>
        </tr>
        <tr>
            <th>NRO</th>
            <th>FECHA</th>
            <th>DENUNCIANTE</th>
            <th>DENUNCIADO</th>
            <th>CONFLICTO</th>
            <th>DESC.</th>
            <th>ESTADO</th>
        </tr>
    </thead>
    <tbody>

        @foreach ($resultados as $item)
            <tr>
                <td>{{ $item->num_denuncia }}</td>
                <td>{{ $item->fecha }}</td>
                <td>{{ $item->denunciante }}</td>
                <td>
                    @if($item->listaDenunciados)
                        <ul>
                            @foreach($item->listaDenunciados as $denunciado)
                                <li>{{ $denunciado->denunciado->nombres }} {{ $denunciado->denunciado->apellido_paterno }} {{ $denunciado->denunciado->apellido_materno }}</li>
                            @endforeach
                        </ul>
                    @else
                        <small>No tiene denunciados</small>
                    @endif
                </td>
                <td>{{ $item->tipo_conflicto }}</td>
                <td>{{ $item->descripcion }}</td>
                <td>{{ $item->estado_denuncia }}</td>
            </tr>
        @endforeach

    </tbody>
</table>
