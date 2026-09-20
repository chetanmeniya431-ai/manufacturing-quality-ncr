<div>
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Settings — Users & Roles</h1>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Linked supplier</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $user)
                    @php $currentRole = $user->roles->first()?->name; @endphp
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            <select wire:change="updateRole({{ $user->id }}, $event.target.value)">
                                @foreach(\App\Livewire\Settings\UserManagement::ROLES as $key => $label)
                                    <option value="{{ $key }}" @selected($currentRole === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-5 py-3">
                            @if($currentRole === 'supplier')
                                <select wire:change="updateRole({{ $user->id }}, 'supplier', $event.target.value)">
                                    <option value="">Select supplier...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($user->supplier_id === $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
