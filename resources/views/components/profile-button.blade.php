<a href="{{ route('profile.edit') }}" class="profile-button" aria-label="Buka profil {{ auth()->user()->fullname }}">
    <span class="profile-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->fullname ?: 'U', 0, 1)) }}</span>
    <span class="profile-identity">
        <strong>{{ auth()->user()->fullname }}</strong>
        <small>{{ auth()->user()->role?->role_name }}</small>
    </span>
</a>
