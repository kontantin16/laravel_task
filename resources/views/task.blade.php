@extends('layouts.app')
@section('sidebar') @stop
@section('content')



  <table id="table1" class="table table-bordered table-hover">
    <thead>
    <tr>
      <th>icon</th>
      <th>date</th>
      <th>name</th>
      <th>phone</th>
      <th>email</th>
     <!-- <th>tmp</th>-->
    </tr>
    </thead>
    <tbody>
    @foreach($leads as $lead)
      <tr id="add_detail" data-id="{{ $lead->id }}" >
        <td> </td>
        <td> {{ $lead->date }} </td>
        <td> {{ $lead->name }} </td>
        <td> {{ $lead->phone }}</td>
        <td> {{ $lead->email }} </td>
      </tr>
    @endforeach
    </tbody>
  </table>
<div class="detail"></div>
@stop