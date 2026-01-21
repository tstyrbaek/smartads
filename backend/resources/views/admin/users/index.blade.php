<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
     
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Create user
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-4">ID</th>
                                    <th class="py-2 pr-4">Name</th>
                                    <th class="py-2 pr-4">Email</th>
                                    <th class="py-2 pr-4">Role</th>
                                    <th class="py-2 pr-4">Created</th>
                                    <th class="py-2 pr-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $user->id }}</td>
                                        <td class="py-2 pr-4">{{ $user->name }}</td>
                                        <td class="py-2 pr-4">{{ $user->email }}</td>
                                        <td class="py-2 pr-4">{{ $user->role }}</td>
                                        <td class="py-2 pr-4">{{ $user->created_at }}</td>
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-indigo-700 hover:text-indigo-900">Edit</a>

                                                @if (Auth::id() !== $user->id)
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete user?');">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="text-sm text-red-700 hover:text-red-900">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
