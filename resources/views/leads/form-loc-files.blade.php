<?php
/**
* required vars
* @param $loc_id: location ID
* @param $files: currently attached files
* @param $total_f_size: total size (in bytes) of all attached files
* @param $is_project: Laravel route name - form URL to submit for deleting attached file
*/

$frm_del_route_name = ($is_project)?  'project.ajax-loc-file-del' : 'lead.ajax-loc-file-del';
$frm_attach_route_name = ($is_project)?  'project.loc-file-attach' : 'lead.loc-file-attach';
?>
<div class="overlay-lead-loc-file">
  <h2>Attached Files</h2>
  
  <ul class="lead-loc-list-files" data-size="{{ $total_f_size }}">
    @forelse ($files as $f)
    <li>
      {!! Form::open(['url'=> route($frm_del_route_name, ['file_id'=> enc_id($f->id)]), 'method'=> 'DELETE', ]) !!}
      <span class="popup-base">
        <i class="md s btn-del-file">close</i>
        <div class="popup-tip left"><div>Delete Attached File</div></div>
      </span>
      <a href="/upload/loc/{{ $f->id }}/{{ $f->url }}">{{ $f->f_desc }}</a>
      {!! Form::close() !!}
    </li>

    @empty
    <li class="err">* There is 0 file attached</li>
    @endforelse
  </ul>
  <div class="spacer-h"></div>
  
  {!! Form::open(['url'=> route($frm_attach_route_name, ['loc_id'=> enc_id($loc_id)]), 'class'=>'frm-file', 'files'=> TRUE]) !!}
    <h3>Attach New File</h3>

    <div class="input-group">
      <div class="file-wrapper">
        {!! Form::file('attachments[]', ['id'=> "f-attach", 'multiple']) !!}
        <label for="f-attach" class="file-label empty">
          <i class="md">file_upload</i>
          <p><b>Click here to browse files</b></p>
          <p class="err">(total file size cannot exceed {{ round(LIMIT_LOCATION_FILE_SIZE_MB, 2) }} MB)</p>

          <div class="preview"></div>
        </label>
      </div>
    </div>

    <div class="btn-group">
      {!! Form::submit('Upload Files') !!}
      {!! Form::button('Cancel', ['class'=> 'btn-cancel']) !!}
    </div>
  {!! Form::close() !!}
</div>
