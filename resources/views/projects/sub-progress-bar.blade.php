<?php
/**
* required vars
* @param $progress_stat: string, status text
* @param $progress_index: 0~(n -1), set "current" on the give indexed item
*/
$progress_items = [
  'New Lead',
  'Lead Management',
  'Project Management',
];
?>
<ul class="lead-progress-bar">
  @foreach ($progress_items as $itm)
    @if ($loop->index === $progress_index)
  <li class="current">{{ $itm }}</li>

    @else
  <li>{{ $itm }}</li>

    @endif
  @endforeach
</ul>
<div style="position: absolute; top: 5px; right: 10px;">
  <b>Status</b>
  <span>{{ $progress_stat }} </span>
</div>
