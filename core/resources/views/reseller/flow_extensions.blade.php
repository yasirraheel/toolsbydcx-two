@extends('reseller.layouts.master')
@section('content')
<div class="card p-4">
    <h4>Client Flow Extensions</h4>
    <p>Generate one-time connection codes for your clients after an administrator assigns their Google Flow account.</p>
    @if(session('flow_code'))
        <div class="alert alert-info">Connection code for {{ session('flow_user') }}: <strong>{{ session('flow_code') }}</strong> (expires in 15 minutes)</div>
    @endif
    <a href="{{ asset('download/extension.zip') }}" class="mb-3">Download extension</a>
    <div class="table-responsive"><table class="table"><thead><tr><th>Client</th><th>Connections</th><th>Actions</th></tr></thead><tbody>
    @forelse($users as $client)
        <tr><td>{{ $client->username }}</td><td>{{ $client->flowPairings->whereNotNull('access_token')->count() }} connected / {{ $client->flowPairings->whereNotNull('pairing_code')->count() }} pending</td><td>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('reseller.flow-extensions.pair', $client->id) }}">@csrf<button type="submit" class="btn btn-sm btn-primary">Generate code</button></form>
                <form method="POST" action="{{ route('reseller.flow-extensions.revoke', $client->id) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button></form>
            </div>
        </td></tr>
    @empty<tr><td colspan="3">No clients found.</td></tr>@endforelse
    </tbody></table></div>
    {{ paginateLinks($users) }}
</div>
@endsection
