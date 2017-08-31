<?php
/**
* required vars
* @param $show_detail: show log-detail or not (1 or 0)
* @param $logs: array of log objects
*/
?>
<ul>
  @forelse ($logs as $log)
  <li class="<?=($log->is_corrected)? 'grayed':'' ?>">
    <div class="clear-fix">
      <b>{{ $log->mod_user }}</b>

      @if ($log->is_corrected)
      <div class="tag-log-corrected"></div>

      @elseif ($log->is_auto_gen)
      <div class="tag-log-system"></div>

      @elseif(Auth::id() == $log->mod_id)
      <div data-id="{{ enc_id($log->id) }}" class="log-action">
        <span class="popup-base">
          <i class="md s btn-log-mod">edit</i>
          <div class="popup-tip"><div>Make Correction</div></div>
        </span>
      </div>

      @endif

    </div>
    <p>[ {{ convert_mysql_timezone($log->date_log, 'm/d/Y h:i a') }} ]</p>
    @if ($log->is_auto_gen)
    <div class="log-msg">{!! $log->log_msg !!}</div>
    @else
    <div class="log-msg manual">{!! nl2br($log->log_msg) !!}</div>
    @endif

    @if ($show_detail)
    <div class="log-detail">{!! $log->log_detail !!}</div>
    @endif
  </li>
  @empty
  @endforelse
</ul>