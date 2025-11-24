@extends('layouts.app')

@section('content')

  @php
    $msg = $msg ?? null;
  @endphp

  <div style="max-width:600px;margin:20px auto;">
    <h3>Employee Quick Login</h3>

    @if($msg)
      <div style="color:green">{{ $msg }}</div>
    @endif

    <p>Open this URL with your id: <code>/login?id=4</code></p>

    <form method="GET" action="/login">
      <label>Employee ID</label>
      <input name="id" />
      <br><br>
      <button type="submit">Login</button>
    </form>
  </div>

@endsection