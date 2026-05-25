@extends('layouts.app')

@section('title', 'Editar Categoría de Crédito')

@section('page')
@include('admin.credit-categories.form', [
    'action' => route('credit-categories.update', $creditCategory),
    'method' => 'PUT',
    'creditCategory' => $creditCategory,
    'rules' => $rules,
])
@endsection
