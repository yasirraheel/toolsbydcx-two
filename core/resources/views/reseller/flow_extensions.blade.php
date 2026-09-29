@extends('reseller.layouts.master')
@section('content')
<div class="card p-4">
    <h4>Client Flow Extensions</h4>
    <p>Clients automatically connect to their assigned Google Flow account through their web session once the extension is installed.</p>
    <a href="{{ route('extension.download') }}" class="btn btn-sm btn--base mb-3"><i class="las la-download me-1"></i>Download extension</a>
    <div class="table-responsive"><table class="table"><thead><tr><th>Client</th><th>Connections</th><th>Actions</th></tr></thead><tbody>
    @forelse($users as $client)
        <tr><td>{{ $client->username }}</td><td>{{ $client->flowPairings->whereNotNull('access_token')->count() }} connected</td><td>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('reseller.flow-extensions.revoke', $client->id) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button></form>
            </div>
        </td></tr>
    @empty<tr><td colspan="3">No clients found.</td></tr>@endforelse
    </tbody></table></div>
    {{ paginateLinks($users) }}
</div>
@endsection
