<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/app.css">
  </head>
  <body class="antialiased">
    {!!$texto!!}
    {{-- <figure class="table">
      <table>
        <tbody>
          <tr>
            <td>
              <img src="{{$message->embed('public/img/LogoSmart3.png')}}" alt="profile Pic" height="100" width="165">
            </td>
            <td>
              <strong>DESPACHOS SEC SEL SAS</strong>
              <br>
              <a href="mailto:despachos@sellosdeseguridad.net">despachos@sellosdeseguridad.net</a>
              <br>NIT 811.031.468-8
              <br>Carrera 51 # 6 Sur 17 Medellín – Colombia
              <br>Tel (604) 4444557 - Fax (604) 3663707
            </td>
          </tr>
        </tbody>
      </table>
    </figure> --}}
  </body>
</html>