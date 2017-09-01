<!DOCTYPE html>
<HTML>
<head>
  <title>@yield('title')</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Cache-Control" content="no-store, private, no-cache, must-revalidate" />
  <meta http-equiv="pragma" content="no-cache" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Code+Pro|Open+Sans:100,100italic,300,300italic,400,400italic,600,600italic|Roboto:100,100italic,300,300italic,400,400italic,500,500italic,600,600italic">
  <link rel="stylesheet" href="//fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="StyleSheet" href="/css/font-awesome.min.css" type=text/css>
  <link rel="StyleSheet" href="/css/ingen.cpanel.css" type="text/css">
  <link rel="StyleSheet" href="/css/ingen.cpanel.agent.css" type="text/css">
</head>

<BODY>
<div class="wrapper @yield('wrapper_type')">
  <?php
  // ************************************** TOAST *********************************************
    $toast_msg = get_toast_msg($errors);
  ?>
  <div id="header-toast" {!! ($toast_msg !='') ? 'style="top:0px"' : '' !!}>
    <div class="btn-toast-close"><i class="md">close</i></div>
    <div class="toast-msg">{!! $toast_msg !!}</div>
  </div>
  <?php
  // ************************************** HEADER file ***************************************
  ?>
  <header>
    <section class="header-main">
      <div class="header-title">
        <h1><a href="{{ route('index') }}"><img src="/img/logo_ingen.svg" /></a></h1>
      </div>
      
      @if (Auth::check())
      <div class="header-icon-menu">
        <a href="{{ route('user.view', ['id'=> enc_id(Auth::id())]) }}" class="popup-base" title="My Account">
          <i class="md md-18">person</i>
          <div class="popup-tip">
            <div>
              <p>{{ trim(Auth::user()->email) }}</p>
              <p>{{ config_pos_name(Auth::user()->access_lv) }}</p>
            </div>
          </div>
        </a>
        <a href="{{ route('logout') }}" title="Log Out" class="btn-header-logout"><span class="fa-power-off"></span></a>
        <a class="btn-header-menu"><span class="fa-bars"></span></a>
      </div>
    @endif
    </section>
  </header>
  @if (Auth::check())
  <nav class="nav-mm">
    <ul>
      <li> <a href="{{ route('home') }}"><i class="md">home</i>Home</a> </li>

      @if ($preapp->lv == POS_LV_AGENT_ADMIN || $preapp->lv == POS_LV_AGENT_MANAGER)
      <li><a href="{{ route('user.list') }}"><i class="md">account_circle</i>Users</a></li>
      <li><a href="{{ route('provider.list') }}"><i class="md">build</i>Service Providers</a></li>
      @endif
      <li><a href="{{ route('lead.list') }}"><i class="md">device_hub</i>Leads</a></li>

      <li>
        <i class="md btn-nav-expand">chevron_right</i>
      </li>
    </ul>
  </nav>
  @endif

  <main>
  @section('content')
  @show
  </main>
  <?php
  // *************** FOOTER ***************
  ?>
  <footer>
    <a href="//ingenlogic.com">Ingenlogic</a> Control Panel
    <div class='developer'>
      <p>Powered by <a href='//{{ DEVELOPER_WEB }}' target='_blank'><span class="title">{!! '<em>'.DEVELOPER_TITLE[0].'</em>'.substr(DEVELOPER_TITLE, 1) !!}</span></a></p>
      <p><a href='mailto:{{ DEVELOPER_EMAIL }}'>Contact Us n</a></p>
    </div>
  </footer>
  <div id="back-to-top" title="Scroll back to Top"></div>
</div>
@section('end_of_body')
@show
<div id="overlay-pane"></div>
</BODY>

<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="/js/ingen.general.js"></script>
<script src="/js/ingen.cpanel.js"></script>

@section('post_content_script')
@show
</HTML>