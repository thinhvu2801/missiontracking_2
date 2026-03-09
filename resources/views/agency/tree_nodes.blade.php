@php
  $nodes = $nodes ?? [];
  $nameKey = $meta['nameKey'] ?? 'name';
  $codeKey = $meta['codeKey'] ?? null;
@endphp

<ul class="tree">
@foreach($nodes as $n)
  @php
    $a = $n['model'];
    $children = $n['children'] ?? [];
    $hasChildren = count($children) > 0;
    $label = $a->{$nameKey} ?? ('Agency #'.$a->id);
    $code  = $codeKey ? ($a->{$codeKey} ?? null) : null;
  @endphp

  <li class="node" id="node-agency-{{ $a->id }}" data-node-type="agency" data-node-id="{{ $a->id }}" data-node-label="{{ e($label) }}" data-text="{{ mb_strtolower($label.' '.($code ?? '').' '.$a->id) }}">
    <div class="rowx">
      <div class="leftx">
        @if($hasChildren)
          <button class="toggle" type="button"><i class="fa fa-chevron-right"></i></button>
        @else
          <span class="spacer"></span>
        @endif

        <span class="chip chip-cyan"><i class="fa fa-building"></i> Cơ quan</span>

        <div class="txt">
          <div class="title">{{ $label }}</div>
        </div>
      </div>

      <div class="act">
        @if(auth()->user()->hasRole('admin') || (auth()->user()->hasRole('sub_admin') && $n['model']->parent_agency_id))
          <button class="iconbtn"
            data-toggle="modal" data-target="#crudModal"
            data-title="Cập nhật cơ quan"
            data-url="{{ route('agencies.edit', ['agency' => $a->id, 'modal' => 1]) }}">
            <i class="fa fa-pencil"></i>
          </button>
        @endif
        @if(auth()->user()->hasRole('admin'))
          <form method="POST" action="{{ route('agencies.destroy', $a) }}" style="display:inline"
                onsubmit="return confirm('Xóa cơ quan này?');">
            @csrf @method('DELETE')
            <button class="iconbtn danger" type="submit"><i class="fa fa-trash"></i></button>
          </form>
        @endif
      </div>
    </div>

    @if($hasChildren)
      <div class="children">
        @include('agency.tree_nodes', ['nodes' => $children, 'meta' => $meta])
      </div>
    @endif
  </li>
@endforeach
</ul>
