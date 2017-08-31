@extends('layouts.master')

@section('title', "Update Agency | ".SITE_TITLE." Control Panel v2")

@section('content')
<div class="panel">
  <h2>Agency Information</h2>

  {!! Form::open(['url'=>route('master.agency.update', ['id'=>enc_id($agency->id)])]) !!}

    @include('master.agencies.form', [
      'agency'=> $agency,
      'row_states'=> $data->row_states,
    ])

    <div class='btn-group'>
      {!! Form::submit('Save Information') !!}
      <a href="{{ route('master.agency.list') }}"><button type="button">Cancel</button></a>
    </div>

  {!! Form::close() !!}
</div>

<div class="panel panel-user-mod-assign-list">
  <h2>Channel Manager</h2>

  {!! Form::open(['url'=>route('master.agency.manager-update', ['id'=> enc_id($agency->id)]), 'class'=> 'frm-manager']) !!}

    @unless (count($data->managers))
    <div class="err">* There is No available Agency</div>

    @else
    <table id="tbl-user-manager-available">
      <thead> <th></th> </thead>
      <tbody>
        @forelse ($data->managers as $r_manager)
        <?php
        $r_enc_id = enc_id($r_manager->id);
        $r_name = trim($r_manager->fname.' '.$r_manager->lname);
        if ($r_manager->active >0)
          $r_td_class = '';
        else {
          $r_td_class = 'grayed';
          $r_name .= ' (Inactive)';
        }
        ?>
        <tr>
          <td class="{{ $r_td_class }}">
            {!! Form::radio('manager_id', $r_enc_id, ($agency->manager_id == $r_manager->id), ['id'=>'r-manager-'.$r_enc_id]) !!}
            <label for="r-manager-{{ $r_enc_id }}">{{ $r_name }}</label>
          </td>
        </tr>
        @empty
        @endforelse
        
      </tbody>
    </table>
    
    @endunless

    <div class="item-unassigned">
      {!! Form::radio('manager_id', 0, ($agency->manager_id <= 0), ['id'=>'r-manager-0']) !!} <label for="r-manager-0">Unassigned</label>
    </div>
    
    <div class='btn-group'>
      {!! Form::submit('Update Manager') !!}
    </div>
  
  {!! Form::close() !!}
</div>
@endsection

@section('post_content_script')
<script src="/js/jquery.dataTables.min.js"></script>
<script>
function mAgencyUpdate() {
  // if user is agent
  $('#tbl-user-manager-available').DataTable({
    autoWidth: false,
    ordering: false,
    scrollY: 400,
    scrollCollapse: true,
    language: { search: '', searchPlaceholder: 'Search Managers'},
    dom: '<ft>',
  });
  $('.frm-manager').submit(function(e) {
    e.preventDefault();
    if ($(this).find('input[name=manager_id]:visible:checked').length <= 0)
      alertUser('Please select a Channel Manager. If manager is not available, please select Unassigned.');
    else
      submitFrm(this);
  });
}
mAgencyUpdate();
</script>
@endsection
