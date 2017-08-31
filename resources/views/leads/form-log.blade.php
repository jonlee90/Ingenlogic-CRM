<?php
/**
 * required vars
 * @param $me: currently logged-in user object (= Auth::user())
 * @param $msg: log message
 */
?>
<div class="input-group">
  <label>User Name</label>
  <div class="output">{{ trim($me->fname.' '.$me->lname) }}</div>
</div>
<div class="input-group">
  <label>Message</label>
  <div class="wrapper-textarea lead-log-textarea">
    {!! Form::textarea('msg', $msg, ['maxlength'=> 500, 'cols'=> 50, 'rows'=> 10 ]) !!}
    <div class="chr-left">{{ strlen(str_replace("\r\n","\n", $msg)) }} / 500</div>
  </div>
</div>