@extends('layouts.app')

@section('title', 'Quản lý Cơ quan')

@push('styles')
  <style>
    :root {
      --line: rgba(15, 23, 42, .10);
      --text: #0f172a;
      --muted: #64748b
    }

    .card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 10px 26px rgba(2, 6, 23, .06)
    }

    .card-h {
      padding: 14px 16px;
      border-bottom: 1px solid rgba(15, 23, 42, .08);
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center
    }

    .card-b {
      padding: 14px 16px
    }

    .btnx {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 12px;
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, .12);
      background: #fff;
      font-weight: 800
    }

    .btnx:hover {
      background: #f8fafc
    }

    .btnx.primary {
      background: rgba(37, 99, 235, .10);
      border-color: rgba(37, 99, 235, .22);
      color: #1d4ed8
    }

    .search {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border: 1px solid rgba(15, 23, 42, .10);
      border-radius: 999px;
      background: #fff
    }

    .search input {
      border: 0;
      outline: 0;
      min-width: 260px
    }

    .tree {
      list-style: none;
      margin: 0;
      padding: 0
    }

    .node {
      margin: 6px 0
    }

    .rowx {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 12px;
      border: 1px solid rgba(15, 23, 42, .10);
      border-radius: 14px;
      background: #fff;
      transition: .16s
    }

    .rowx:hover {
      background: rgba(37, 99, 235, .06);
      border-color: rgba(37, 99, 235, .22);
      transform: translateY(-1px);
      box-shadow: 0 10px 22px rgba(2, 6, 23, .07)
    }

    .leftx {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0
    }

    .title {
      font-weight: 900;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 720px
    }

    .meta {
      color: var(--muted);
      font-weight: 800;
      font-size: 12px
    }

    .txt {
      min-width: 0
    }

    .act {
      display: flex;
      align-items: center;
      gap: 6px;
      flex: 0 0 auto
    }

    .toggle {
      width: 32px;
      height: 32px;
      border-radius: 12px;
      border: 1px solid rgba(15, 23, 42, .10);
      background: #fff
    }

    .toggle i {
      transition: transform .18s
    }

    .node.open>.rowx .toggle i {
      transform: rotate(90deg)
    }

    .spacer {
      display: inline-block;
      width: 32px
    }

    .children {
      overflow: hidden;
      max-height: 0;
      transition: max-height .22s ease;
      margin-left: 18px;
      padding-left: 12px;
      border-left: 1px dashed rgba(15, 23, 42, .14)
    }

    .node.open>.children {
      max-height: 2400px;
      margin-top: 8px
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      font-weight: 900;
      font-size: 12px;
      border: 1px solid rgba(15, 23, 42, .08);
      background: rgba(15, 23, 42, .05)
    }

    .chip-blue {
      background: rgba(37, 99, 235, .10);
      border-color: rgba(37, 99, 235, .18);
      color: #1d4ed8
    }

    .chip-cyan {
      background: rgba(6, 182, 212, .10);
      border-color: rgba(6, 182, 212, .18);
      color: #0e7490
    }

    .iconbtn {
      width: 34px;
      height: 34px;
      border-radius: 12px;
      border: 1px solid rgba(15, 23, 42, .12);
      background: #fff
    }

    .iconbtn:hover {
      background: #f8fafc
    }

    .iconbtn.danger {
      border-color: rgba(239, 68, 68, .22);
      background: rgba(239, 68, 68, .06);
      color: #b91c1c
    }

    .dim {
      opacity: .35
    }

    .match>.rowx {
      border-color: rgba(6, 182, 212, .35);
      background: rgba(6, 182, 212, .08)
    }

    .modal-dialog.modal-xl {
      width: 92%;
      max-width: 1100px
    }

    .modal-iframe {
      width: 100%;
      height: 78vh;
      border: 0;
      display: block
    }

    @media (max-width:768px) {
      .search input {
        min-width: 160px
      }

      .title {
        max-width: 360px
      }
    }
  
    /* Search results list */
    .sres{display:flex;flex-direction:column;gap:8px}
    .sres-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid rgba(15,23,42,.10);border-radius:14px;background:#fff}
    .sres-left{display:flex;align-items:center;gap:10px;min-width:0}
    .sres-title{font-weight:900;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:560px}
    .sres-meta{color:var(--muted);font-weight:800;font-size:12px}
    .sres-go{white-space:nowrap}
    .flash{animation:flashPulse 1.2s ease-in-out 0s 2}
    @keyframes flashPulse{0%{box-shadow:0 0 0 0 rgba(6,182,212,.0)}30%{box-shadow:0 0 0 6px rgba(6,182,212,.25)}100%{box-shadow:0 0 0 0 rgba(6,182,212,.0)}}

  </style>
@endpush

@section('content')
  <div
    style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;align-items:flex-start">
    <div>
      <h2 style="margin:0;font-weight:900">QUẢN LÝ CƠ QUAN</h2>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      @if(auth()->user()->hasRole('admin'))
        <button class="btnx primary" data-toggle="modal" data-target="#crudModal" data-title="Thêm nhóm cơ quan"
          data-url="{{ route('agency-groups.create', ['modal' => 1]) }}">
          <i class="fa fa-plus"></i> Thêm nhóm cơ quan
        </button>
      @endif

      @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('sub_admin'))
        <button class="btnx primary" data-toggle="modal" data-target="#crudModal" data-title="Thêm cơ quan"
          data-url="{{ route('agencies.create', ['modal' => 1]) }}">
          <i class="fa fa-plus"></i> Thêm cơ quan
        </button>
      @endif

      <button class="btnx" type="button" onclick="treeAll(true)"><i class="fa fa-angle-double-down"></i> Mở hết</button>
      <button class="btnx" type="button" onclick="treeAll(false)"><i class="fa fa-angle-double-up"></i> Đóng hết</button>

      <div class="search">
        <i class="fa fa-search" style="color:#94a3b8"></i>
        <input id="treeSearch" placeholder="Tìm nhóm hoặc cơ quan...">
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success"
      style="border-radius:14px;border:1px solid rgba(16,185,129,.20);background:rgba(16,185,129,.08);color:#065f46;font-weight:800">
      {{ session('success') }}
    </div>
  @endif

  <div class="card">
    <div class="card-h">
      <div style="font-weight:900">Nhóm Cơ quan</div>
      <div style="color:#94a3b8;font-weight:800;font-size:12px">{{ $groups->count() ?? 0 }} nhóm</div>
    </div>

    <div class="card-b">
      <div id="searchResultsWrap" style="display:none;margin-bottom:12px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px">
          <div style="font-weight:900">Kết quả tìm kiếm: <span id="searchResultsCount">0</span></div>
          <button type="button" class="btnx" onclick="clearTreeSearch()"><i class="fa fa-times"></i> Xóa tìm kiếm</button>
        </div>
        <div id="searchResults" class="sres"></div>
      </div>

      <ul class="tree" id="treeRoot">
        @foreach($groups as $group)
          @php $gNodes = $groupTrees[$group->id] ?? []; @endphp

          <li class="node open" id="node-group-{{ $group->id }}" data-node-type="group" data-node-id="{{ $group->id }}" data-node-label="{{ e($group->group_name ?? '' ) }}" data-text="{{ mb_strtolower(($group->group_name ?? '') . ' ' . $group->id) }}">
            <div class="rowx">
              <div class="leftx">
                <button class="toggle" type="button"><i class="fa fa-chevron-right"></i></button>
                <span class="chip chip-blue"><i class="fa fa-folder-open"></i> Nhóm</span>
                <div class="txt">
                  <div class="title">{{ $group->group_name }}</div>
                </div>
              </div>
              <div class="act">
                @if(auth()->user()->hasRole('admin'))
                  <button class="iconbtn" data-toggle="modal" data-target="#crudModal" data-title="Cập nhật nhóm cơ quan"
                    data-url="{{ route('agency-groups.edit', ['agency_group' => $group->id, 'modal' => 1]) }}">
                    <i class="fa fa-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('agency-groups.destroy', $group) }}" style="display:inline"
                    onsubmit="return confirm('Xóa nhóm này?');">
                    @csrf @method('DELETE')
                    <button class="iconbtn danger" type="submit"><i class="fa fa-trash"></i></button>
                  </form>
                @endif
              </div>            
            </div>

            <div class="children">
              @if(count($gNodes))
                @include('agency.tree_nodes', ['nodes' => $gNodes, 'meta' => $meta])
              @else
                <div style="margin:10px 0 0 18px;color:#94a3b8;font-weight:800">Chưa có cơ quan trong nhóm này.</div>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>

  {{-- CRUD MODAL --}}
  <div class="modal fade" id="crudModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content" style="border-radius:18px;overflow:hidden">
        <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between">
          <h4 class="modal-title" id="crudModalTitle" style="font-weight:900;margin:0">Cập nhật</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity:1">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="padding:0">
          <iframe id="crudModalFrame" class="modal-iframe" src="about:blank"></iframe>
        </div>
      </div>
    </div>
  </div>
@endsection


@push('scripts')
  <script>
    (function () {
      // Toggle open/close
      document.getElementById('treeRoot')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.toggle');
        if (!btn) return;
        const li = btn.closest('.node');
        li.classList.toggle('open');
      });

      window.treeAll = function (open) {
        document.querySelectorAll('#treeRoot .node').forEach(n => {
          if (open) n.classList.add('open');
          else n.classList.remove('open');
        });
      }

      // Modal load
      $('#crudModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        $('#crudModalTitle').text(button.data('title') || 'Cập nhật');
        $('#crudModalFrame').attr('src', button.data('url') || 'about:blank');
      });
      $('#crudModal').on('hidden.bs.modal', function () {
        $('#crudModalFrame').attr('src', 'about:blank');
      });

      // Listen postMessage from modal_done
      window.addEventListener('message', function (ev) {
        if (!ev || !ev.data) return;
        if (ev.data.type === 'agency-crud-done') {
          try { $('#crudModal').modal('hide'); } catch (e) { }
          location.reload();
        }
      });

            // Search: highlight + open ancestors + results list (pin on top)
      const input = document.getElementById('treeSearch');
      const wrap = document.getElementById('searchResultsWrap');
      const list = document.getElementById('searchResults');
      const countEl = document.getElementById('searchResultsCount');

      function openAncestors(li) {
        let p = li.parentElement;
        while (p) {
          const anc = p.closest('.node');
          if (anc) anc.classList.add('open');
          p = anc ? anc.parentElement : null;
        }
      }

      function scrollToNode(nodeId) {
        const el = document.getElementById(nodeId);
        if (!el) return;
        openAncestors(el);
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // flash highlight
        const row = el.querySelector('.rowx');
        if (row) {
          row.classList.remove('flash');
          // force reflow
          void row.offsetWidth;
          row.classList.add('flash');
          setTimeout(() => row.classList.remove('flash'), 2600);
        }
      }

      window.clearTreeSearch = function () {
        if (!input) return;
        input.value = '';
        input.dispatchEvent(new Event('input'));
        input.focus();
      }

      function escapeHtml(str) {
        return String(str || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function renderResults(matches, q) {
        if (!wrap || !list || !countEl) return;

        if (!q) {
          wrap.style.display = 'none';
          list.innerHTML = '';
          countEl.textContent = '0';
          return;
        }

        countEl.textContent = String(matches.length);
        wrap.style.display = 'block';

        if (!matches.length) {
          list.innerHTML = `<div style="color:#94a3b8;font-weight:800">Không tìm thấy kết quả cho “${escapeHtml(q)}”.</div>`;
          return;
        }

        // Sort: agencies first, then groups; then by label
        matches.sort((a, b) => {
          const ta = (a.dataset.nodeType || '');
          const tb = (b.dataset.nodeType || '');
          if (ta !== tb) return ta === 'agency' ? -1 : 1;
          const la = (a.dataset.nodeLabel || a.querySelector('.title')?.textContent || '').toLowerCase();
          const lb = (b.dataset.nodeLabel || b.querySelector('.title')?.textContent || '').toLowerCase();
          return la.localeCompare(lb);
        });

        const limit = 60;
        const sliced = matches.slice(0, limit);

        const html = sliced.map(li => {
          const type = li.dataset.nodeType || (li.querySelector('.chip-blue') ? 'group' : 'agency');
          const id = li.dataset.nodeId || '';
          const label = li.dataset.nodeLabel || li.querySelector('.title')?.textContent || '';
          const nodeId = li.id || '';

          const chip = type === 'group'
            ? `<span class="chip chip-blue"><i class="fa fa-folder-open"></i> Nhóm</span>`
            : `<span class="chip chip-cyan"><i class="fa fa-building"></i> Cơ quan</span>`;

          const meta = type === 'group' ? `ID nhóm: ${id}` : `ID cơ quan: ${id}`;

          return `
            <div class="sres-item">
              <div class="sres-left">
                ${chip}
                <div style="min-width:0">
                  <div class="sres-title" title="${escapeHtml(label)}">${escapeHtml(label)}</div>
                  <div class="sres-meta">${meta}</div>
                </div>
              </div>
              <button class="btnx sres-go" type="button" data-node="${nodeId}">
                <i class="fa fa-location-arrow"></i> Đi tới
              </button>
            </div>
          `;
        }).join('');

        const more = matches.length > limit
          ? `<div style="color:#94a3b8;font-weight:800">Đang hiển thị ${limit}/${matches.length} kết quả. Hãy gõ thêm ký tự để lọc hẹp.</div>`
          : '';

        list.innerHTML = html + more;

        // bind clicks
        list.querySelectorAll('button[data-node]').forEach(btn => {
          btn.addEventListener('click', () => scrollToNode(btn.getAttribute('data-node')));
        });
      }

      if (input) {
        let t = null;
        input.addEventListener('input', () => {
          clearTimeout(t);
          t = setTimeout(() => {
            const q = input.value.trim().toLowerCase();
            const nodes = [...document.querySelectorAll('#treeRoot .node')];

            // reset state
            nodes.forEach(n => { n.classList.remove('match'); n.classList.remove('dim'); });

            if (!q) {
              renderResults([], '');
              return;
            }

            // dim everything first
            nodes.forEach(n => n.classList.add('dim'));

            const matches = [];
            nodes.forEach(n => {
              const hit = (n.getAttribute('data-text') || '').includes(q);
              if (hit) {
                matches.push(n);
                n.classList.add('match');
                n.classList.remove('dim');
                openAncestors(n);
              }
            });

            // ensure ancestors of matches are not dim
            matches.forEach(n => {
              let p = n.parentElement;
              while (p) {
                const anc = p.closest('.node');
                if (anc) anc.classList.remove('dim');
                p = anc ? anc.parentElement : null;
              }
            });

            renderResults(matches, input.value.trim());
          }, 120);
        });
      }
    })();
  </script>
@endpush