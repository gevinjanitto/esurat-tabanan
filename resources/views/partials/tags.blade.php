@php $tagList = $tags ?? []; @endphp
<div class="{{ $class ?? '' }}" data-testid="tags-widget-{{ $type }}-{{ $id }}">
    <div class="flex flex-wrap gap-1.5 mb-2" data-tag-list>
        @forelse ($tagList as $t)<span class="badge badge-gold">#{{ $t }}</span>@empty<span class="text-xs text-slate-400">Belum ada tag</span>@endforelse
    </div>
    <form method="POST" action="{{ route('tags.save') }}" class="flex gap-2" data-tag-form>@csrf
        <input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="id" value="{{ $id }}">
        <input name="tags" value="{{ implode(', ', $tagList) }}" class="input !py-2 text-sm" placeholder="mis. rapat, anggaran" data-testid="tags-input-{{ $type }}-{{ $id }}">
        <button class="btn btn-soft !px-3" data-testid="tags-save-{{ $type }}-{{ $id }}"><i data-lucide="tag" class="w-4 h-4"></i></button>
    </form>
    <p class="text-[10px] text-slate-400 mt-1">Tag pribadi, pisahkan dengan koma. Hanya terlihat oleh Anda.</p>
</div>
