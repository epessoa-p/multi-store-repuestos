@extends('layouts.app')

@section('title', 'Nueva Categoría de Crédito')

@section('page')
@include('admin.credit-categories.form', [
    'action' => route('credit-categories.store'),
    'method' => 'POST',
    'creditCategory' => $creditCategory,
    'rules' => $rules,
])
@endsection
