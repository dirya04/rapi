@php
    $user = auth()->user();
    $role = $user?->role?->role_name;
    $isAdmin = in_array($role, ['Admin', 'Waka'], true);
    $status = data_get($event, 'status');
    $adminApprover = data_get($event, 'approved_by');
    $needsApproval = $status === 'pending' && $isAdmin && $adminApprover === null;
@endphp

@if($needsApproval)
    <form action="{{ route('events.decide', $event->id) }}" method="POST" class="approval-actions">
        @csrf
        <button type="submit" name="status" value="approved" class="btn btn-dark btn-sm">Setujui</button>
        <button type="submit" name="status" value="rejected" class="btn btn-sm" onclick="return confirm('Tolak pengajuan agenda ini?')">Tolak</button>
    </form>
@endif
