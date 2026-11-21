@props(['status'])

<span @class([
    'px-2 py-1 rounded text-xs font-medium',
    'bg-yellow-100 text-yellow-800' => $status === 'pending',
    'bg-green-100 text-green-800'   => $status === 'confirmed',
    'bg-red-100 text-red-800'       => $status === 'cancelled',
])>{{ ucfirst($status) }}</span>