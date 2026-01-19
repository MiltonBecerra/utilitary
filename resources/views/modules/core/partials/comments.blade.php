@if($utility)
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card fx-comment-card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-comments"></i> Comentarios</h5></div>
                    <div class="card-body">
                        <h6 class="mb-3">Deja tu comentario</h6>
                        <form action="{{ route('utilities.comments.store', $utility) }}" method="POST" id="main-comment-form" class="comment-form mb-4" data-no-loader novalidate>
                            @csrf
                            @auth
                                <input type="hidden" name="name" value="{{ Auth::user()->name }}">
                            @endauth
                            @guest
                            <div class="form-group guest-name-block">
                                <label>Nombre</label>
                                <input type="text" name="name" class="form-control guest-name-input">
                                <div class="d-flex align-items-center mt-1 guest-name-display d-none">
                                    <small class="text-muted mb-0">Comentando como <strong class="guest-name-label"></strong></small>
                                    <button type="button" class="btn btn-link btn-sm ml-2 guest-name-change">Cambiar</button>
                                </div>
                            </div>
                            @endguest
                            <div class="form-group">
                                <label>Comentario</label>
                                <input type="text" name="comment" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane submit-icon"></i>
                                <i class="fas fa-circle-notch fa-spin d-none submit-spinner"></i>
                                Enviar
                            </button>
                        </form>

                        <div id="comments-list">
                            @forelse($comments->whereNull('parent_id')->take(10) as $comment)
                            <div class="border-bottom pb-3 mb-3 comment" data-comment-id="{{ $comment->id }}">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $comment->name }}</strong>
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-2">{{ $comment->comment }}</p>
                                <div class="d-flex align-items-center flex-wrap mb-2">
                                    @php $reactionCounts = $comment->reactions->groupBy('type')->map->count(); @endphp
                                    <button class="btn btn-sm btn-outline-primary mr-2 reaction-btn" data-type="like" data-url="{{ route('comments.react', $comment) }}"><i class="far fa-thumbs-up"></i> <span class="reaction-count">{{ $reactionCounts['like'] ?? 0 }}</span></button>
                                    <button class="btn btn-sm btn-outline-secondary mr-2 reaction-btn" data-type="sad" data-url="{{ route('comments.react', $comment) }}"><i class="far fa-frown"></i> <span class="reaction-count">{{ $reactionCounts['sad'] ?? 0 }}</span></button>
                                    <button class="btn btn-sm btn-outline-success mr-2 reaction-btn" data-type="laugh" data-url="{{ route('comments.react', $comment) }}"><i class="far fa-laugh"></i> <span class="reaction-count">{{ $reactionCounts['laugh'] ?? 0 }}</span></button>
                                    <button class="btn btn-sm btn-outline-danger mr-2 reaction-btn" data-type="angry" data-url="{{ route('comments.react', $comment) }}"><i class="far fa-angry"></i> <span class="reaction-count">{{ $reactionCounts['angry'] ?? 0 }}</span></button>
                                    <button class="btn btn-sm btn-link toggle-reply" data-target="#reply-{{ $comment->id }}">Responder</button>
                                </div>
                                @if($comment->email)
                                    <small class="text-muted d-block mb-2"><i class="fas fa-envelope"></i> {{ $comment->email }}</small>
                                @endif
                                <div class="collapse" id="reply-{{ $comment->id }}">
                                    <form action="{{ route('utilities.comments.store', $utility) }}" method="POST" class="mt-2 reply-form" data-no-loader novalidate>
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        @auth
                                            <input type="hidden" name="name" value="{{ Auth::user()->name }}">
                                        @endauth
                                        @guest
                                        <div class="form-group guest-name-block">
                                            <label>Nombre</label>
                                            <input type="text" name="name" class="form-control guest-name-input">
                                            <div class="d-flex align-items-center mt-1 guest-name-display d-none">
                                                <small class="text-muted mb-0">Comentando como <strong class="guest-name-label"></strong></small>
                                                <button type="button" class="btn btn-link btn-sm ml-2 guest-name-change">Cambiar</button>
                                            </div>
                                        </div>
                                        @endguest
                                        <div class="form-group">
                                            <label>Respuesta</label>
                                            <input type="text" name="comment" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-reply submit-icon"></i>
                                            <i class="fas fa-circle-notch fa-spin d-none submit-spinner"></i>
                                            Responder
                                        </button>
                                    </form>
                                </div>
                                @if($comment->replies->count())
                                    <div class="mt-3 ml-3 replies">
                                        @foreach($comment->replies as $reply)
                                            <div class="border-left pl-3 mb-2" data-comment-id="{{ $reply->id }}">
                                                <div class="d-flex justify-content-between">
                                                    <strong>{{ $reply->name }}</strong>
                                                    <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1">{{ $reply->comment }}</p>
                                                @if($reply->email)<small class="text-muted"><i class="fas fa-envelope"></i> {{ $reply->email }}</small>@endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-3 ml-3 replies"></div>
                                @endif
                            </div>
                            @empty
                                <p class="text-muted mb-0">Aún no hay comentarios para este utilitario.</p>
                            @endforelse
                        </div>
                        <div id="comments-sentinel" class="text-center text-muted py-2" data-next-url="{{ route('utilities.comments.index', $utility) }}?page=2">
                            <div id="comments-loader" class="d-none">
                                <img src="https://i.gifer.com/YCZH.gif" alt="Cargando..." style="height:32px;">
                                <div>Cargando más comentarios...</div>
                            </div>
                        </div>
                        <div id="comments-sentinel" class="text-center text-muted py-2" data-next-url="{{ route('utilities.comments.index', $utility) }}?page=2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('page_css')
<style>
.fx-comment-card {
    background: var(--fx-surface, #ffffff);
    border-radius: 16px;
    border: 1px solid var(--fx-border, rgba(15, 23, 42, 0.08));
    box-shadow: var(--fx-shadow-sm, 0 8px 20px rgba(15, 23, 42, 0.08));
}
.comment {
    position: relative;
    padding-left: 44px;
    padding-top: 4px;
    min-height: 36px;
}
.comment::before {
    content: attr(data-initial);
    position: absolute;
    left: 0;
    top: 2px;
    width: var(--fx-comment-avatar, 36px);
    height: var(--fx-comment-avatar, 36px);
    border-radius: 50%;
    background: rgba(37, 99, 235, 0.12);
    color: var(--fx-primary, #2563eb);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fraunces', serif;
}
.fx-comment-card .replies > div {
    position: relative;
    padding-left: 36px !important;
    padding-top: 4px;
    min-height: 32px;
}
.fx-comment-card .replies > div::before {
    content: attr(data-initial);
    position: absolute;
    left: 0;
    top: 2px;
    width: var(--fx-reply-avatar, 28px);
    height: var(--fx-reply-avatar, 28px);
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.2);
    color: var(--fx-muted, #64748b);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fraunces', serif;
    font-size: 0.75rem;
}
</style>
@endpush

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const commentsList = document.getElementById('comments-list');
    const mainCommentForm = document.getElementById('main-comment-form');
    if (!commentsList && !mainCommentForm) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const currentUserName = @json(Auth::check() ? Auth::user()->name : null);
    const isGuest = @json(!Auth::check());
    const storageKey = 'guest_comment_name';
    const storedName = isGuest ? localStorage.getItem(storageKey) : null;
    const blocks = document.querySelectorAll('.guest-name-block');

    const applyName = (block, name) => {
        if (!block) return;
        const input = block.querySelector('.guest-name-input');
        const display = block.querySelector('.guest-name-display');
        const label = block.querySelector('.guest-name-label');
        const title = block.querySelector('label');
        if (!input || !display || !label) return;

        if (name && name.trim() !== '') {
            label.textContent = name;
            input.classList.add('d-none');
            input.required = false;
            display.classList.remove('d-none');
            if (title) title.classList.add('d-none');
        } else {
            input.classList.remove('d-none');
            input.required = true;
            display.classList.add('d-none');
            label.textContent = '';
            if (title) title.classList.remove('d-none');
        }
    };

    if (isGuest && storedName) {
        blocks.forEach(block => {
            const input = block.querySelector('.guest-name-input');
            if (input) input.value = storedName;
            applyName(block, storedName);
        });
    }

    document.body.addEventListener('click', function (e) {
        const changeBtn = e.target.closest('.guest-name-change');
        if (!changeBtn) return;

        const block = changeBtn.closest('.guest-name-block');
        if (!block) return;

        localStorage.removeItem(storageKey);
        const input = block.querySelector('.guest-name-input');
        const display = block.querySelector('.guest-name-display');
        if (input) {
            input.classList.remove('d-none');
            input.required = true;
            input.value = '';
            input.focus();
        }
        if (display) display.classList.add('d-none');
        const title = block.querySelector('label');
        if (title) title.classList.remove('d-none');
    });

    const commentStoreUrl = "{{ route('utilities.comments.store', $utility) }}";

    const getInitial = (name) => {
        const clean = (name || '').trim();
        return clean ? clean.charAt(0).toUpperCase() : '?';
    };

    const applyInitials = (scope = document) => {
        scope.querySelectorAll('.comment').forEach(comment => {
            if (comment.dataset.initial) return;
            const strong = comment.querySelector('strong');
            comment.dataset.initial = getInitial(strong ? strong.textContent : '');
        });
        scope.querySelectorAll('.replies > div').forEach(reply => {
            if (reply.dataset.initial) return;
            const strong = reply.querySelector('strong');
            reply.dataset.initial = getInitial(strong ? strong.textContent : '');
        });
    };

    applyInitials();

    const renderReply = (parentEl, comment) => {
        let replies = parentEl.querySelector('.replies');
        if (!replies) {
            replies = document.createElement('div');
            replies.className = 'mt-3 ml-3 replies';
            parentEl.appendChild(replies);
        }
        const replyDiv = document.createElement('div');
        replyDiv.className = 'border-left pl-3 mb-2';
        replyDiv.dataset.commentId = comment.id;
        replyDiv.dataset.initial = getInitial(comment.name);

        replyDiv.innerHTML = `
            <div class="d-flex justify-content-between">
                <strong></strong>
                <small class="text-muted"></small>
            </div>
            <p class="mb-1"></p>
            ${comment.email ? `<small class="text-muted"><i class="fas fa-envelope"></i> ${comment.email}</small>` : ''}
        `;
        replyDiv.querySelector('strong').textContent = comment.name;
        replyDiv.querySelector('small.text-muted').textContent = comment.created_at_human || 'Justo ahora';
        replyDiv.querySelector('p').textContent = comment.comment;
        if (replies.firstChild) {
            replies.insertBefore(replyDiv, replies.firstChild);
        } else {
            replies.appendChild(replyDiv);
        }
    };

    const renderComment = (comment) => {
        if (!commentsList) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'border-bottom pb-3 mb-3 comment';
        wrapper.dataset.commentId = comment.id;
        wrapper.dataset.initial = getInitial(comment.name);
        const replyTarget = `reply-${comment.id}`;
        const reactUrl = comment.react_url || `/comments/${comment.id}/react`;
        const counts = comment.reactions || {};

        wrapper.innerHTML = `
            <div class="d-flex justify-content-between">
                <strong></strong>
                <small class="text-muted"></small>
            </div>
            <p class="mb-2"></p>
            <div class="d-flex align-items-center flex-wrap mb-2">
                <button class="btn btn-sm btn-outline-primary mr-2 reaction-btn" data-type="like" data-url="${reactUrl}"><i class="far fa-thumbs-up"></i> <span class="reaction-count">${counts.like || 0}</span></button>
                <button class="btn btn-sm btn-outline-secondary mr-2 reaction-btn" data-type="sad" data-url="${reactUrl}"><i class="far fa-frown"></i> <span class="reaction-count">${counts.sad || 0}</span></button>
                <button class="btn btn-sm btn-outline-success mr-2 reaction-btn" data-type="laugh" data-url="${reactUrl}"><i class="far fa-laugh"></i> <span class="reaction-count">${counts.laugh || 0}</span></button>
                <button class="btn btn-sm btn-outline-danger mr-2 reaction-btn" data-type="angry" data-url="${reactUrl}"><i class="far fa-angry"></i> <span class="reaction-count">${counts.angry || 0}</span></button>
                <button class="btn btn-sm btn-link toggle-reply" data-target="#${replyTarget}">Responder</button>
            </div>
            ${comment.email ? `<small class="text-muted d-block mb-2"><i class="fas fa-envelope"></i> ${comment.email}</small>` : ''}
            <div class="collapse" id="${replyTarget}" style="display:none;">
                <form action="${commentStoreUrl}" method="POST" class="mt-2 reply-form" data-no-loader novalidate>
                    <input type="hidden" name="_token" value="${csrfToken || ''}">
                    <input type="hidden" name="parent_id" value="${comment.id}">
                    ${isGuest ? `
                    <div class="form-group guest-name-block">
                        <label>Nombre</label>
                        <input type="text" name="name" class="form-control guest-name-input">
                        <div class="d-flex align-items-center mt-1 guest-name-display d-none">
                            <small class="text-muted mb-0">Comentando como <strong class="guest-name-label"></strong></small>
                            <button type="button" class="btn btn-link btn-sm ml-2 guest-name-change">Cambiar</button>
                        </div>
                    </div>` : `<input type="hidden" name="name" value="${currentUserName || ''}">`}
                    <div class="form-group">
                        <label>Respuesta</label>
                        <input type="text" name="comment" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-reply submit-icon"></i>
                        <i class="fas fa-circle-notch fa-spin d-none submit-spinner"></i>
                        Responder
                    </button>
                </form>
            </div>
            <div class="mt-3 ml-3 replies"></div>
        `;

        wrapper.querySelector('strong').textContent = comment.name;
        wrapper.querySelector('small.text-muted').textContent = comment.created_at_human || 'Justo ahora';
        wrapper.querySelector('p').textContent = comment.comment;

        if (commentsList.firstChild) {
            commentsList.insertBefore(wrapper, commentsList.firstChild);
        } else {
            commentsList.appendChild(wrapper);
        }

        if (isGuest && storedName) {
            applyName(wrapper.querySelector('.guest-name-block'), storedName);
        }
    };

    const sentinel = document.getElementById('comments-sentinel');
    const loader = document.getElementById('comments-loader');
    let nextCommentsUrl = sentinel ? sentinel.dataset.nextUrl : null;
    const loadingComments = { value: false };

    const loadMoreComments = async () => {
        if (!nextCommentsUrl || loadingComments.value) return;
        loadingComments.value = true;
        try {
            if (loader) loader.classList.remove('d-none');
            const res = await fetch(nextCommentsUrl, { headers: { 'Accept': 'application/json', 'X-No-Loader': '1' } });
            if (!res.ok) throw new Error('Error cargando comentarios');
            const data = await res.json();
            if (Array.isArray(data.data)) {
                data.data.forEach(c => {
                    renderComment({
                        id: c.id,
                        parent_id: c.parent_id,
                        name: c.name,
                        email: c.email,
                        comment: c.comment,
                        created_at_human: c.created_at_human,
                        reactions: c.reactions || {},
                    });
                    if (Array.isArray(c.replies)) {
                        const parentEl = document.querySelector(`.comment[data-comment-id="${c.id}"]`);
                        c.replies.forEach(r => parentEl && renderReply(parentEl, r));
                    }
                });
            }
            nextCommentsUrl = data.next_page_url;
            if (!nextCommentsUrl && sentinel) {
                sentinel.textContent = 'No hay mas comentarios';
            }
        } catch (e) {
            console.error(e);
        } finally {
            if (loader) loader.classList.add('d-none');
            loadingComments.value = false;
        }
    };

    if (sentinel) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadMoreComments();
                }
            });
        });
        observer.observe(sentinel);
    }

    const handleCommentSubmit = async (form) => {
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitIcon = submitBtn ? submitBtn.querySelector('.submit-icon') : null;
        const submitSpinner = submitBtn ? submitBtn.querySelector('.submit-spinner') : null;
        if (submitBtn) submitBtn.disabled = true;
        if (submitIcon) submitIcon.classList.add('d-none');
        if (submitSpinner) submitSpinner.classList.remove('d-none');
        const nameInput = form.querySelector('.guest-name-input');
        if (nameInput) {
            const isHidden = nameInput.classList.contains('d-none') || nameInput.offsetParent === null || getComputedStyle(nameInput).display === 'none';
            nameInput.required = !isHidden;
            if (isHidden) nameInput.removeAttribute('required');
        }
        const input = form.querySelector('.guest-name-input');
        if (isGuest && input) {
            if ((input.classList.contains('d-none') || input.value.trim() === '') && storedName) {
                input.value = storedName;
            }
            if (input.value.trim() !== '') {
                const name = input.value.trim();
                localStorage.setItem(storageKey, name);
                blocks.forEach(block => applyName(block, name));
            } else {
                input.classList.remove('d-none');
                input.required = true;
                input.focus();
                return;
            }
        }

        const action = form.getAttribute('action');
        const formData = new FormData(form);
        if (csrfToken) {
            formData.set('_token', csrfToken);
        }
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        try {
            const res = await fetch(action, {
                method: 'POST',
                headers: { ...headers, 'X-No-Loader': '1' },
                body: formData
            });
            if (!res.ok) throw new Error('Error al enviar comentario');
            const data = await res.json().catch(() => null);
            const parentId = (form.querySelector('input[name="parent_id"]')?.value || '').trim();
            if (data && data.comment) {
                if (parentId) {
                    const parentEl = document.querySelector(`.comment[data-comment-id="${parentId}"]`);
                    if (parentEl) renderReply(parentEl, data.comment);
                    const collapse = form.closest('.collapse');
                    if (collapse) {
                        collapse.classList.remove('show');
                        collapse.style.display = 'none';
                    }
                } else {
                    renderComment(data.comment);
                }
                form.reset();
            } else {
                const input = form.querySelector('input[name="comment"]');
                if (input) input.value = '';
            }
        } catch (error) {
            console.error('Error enviando comentario', error);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (submitIcon) submitIcon.classList.remove('d-none');
            if (submitSpinner) submitSpinner.classList.add('d-none');
        }
    };

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches('form.comment-form, form.reply-form')) return;
        form.setAttribute('novalidate', 'novalidate');
        e.preventDefault();
        document.querySelectorAll('.guest-name-input').forEach(inp => {
            const hidden = inp.classList.contains('d-none') || inp.offsetParent === null || getComputedStyle(inp).display === 'none';
            if (hidden) {
                inp.required = false;
                inp.removeAttribute('required');
            } else {
                inp.required = true;
            }
        });
        handleCommentSubmit(form);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (e.shiftKey || e.ctrlKey || e.altKey || e.metaKey) return;
        const input = e.target;
        if (!input || !input.matches('input[name="comment"]')) return;
        const form = input.closest('form.comment-form, form.reply-form');
        if (!form) return;
        e.preventDefault();
        handleCommentSubmit(form);
    });

    document.querySelectorAll('.guest-name-input').forEach(input => {
        input.required = false;
    });

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.reaction-btn');
        if (!btn) return;
        e.preventDefault();
        const url = btn.dataset.url;
        const type = btn.dataset.type;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!url || !type || !token) return;
        btn.disabled = true;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-No-Loader': '1'
                },
                body: JSON.stringify({ type })
            });
            if (!res.ok) throw new Error('Error al reaccionar');
            const data = await res.json().catch(() => null);
            const commentEl = btn.closest('.comment');
            const buttons = commentEl ? commentEl.querySelectorAll('.reaction-btn') : [];
            const countsFromServer = data && data.counts ? data.counts : null;

            buttons.forEach(b => {
                const span = b.querySelector('.reaction-count');
                const bType = b.dataset.type;
                if (!span) return;
                if (countsFromServer) {
                    span.textContent = countsFromServer[bType] || 0;
                } else {
                    span.textContent = (b === btn) ? 1 : 0;
                }
            });
        } finally {
            btn.disabled = false;
        }
    });

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.toggle-reply');
        if (!toggle) return;
        e.preventDefault();
        const target = document.querySelector(toggle.dataset.target);
        if (!target) return;
        target.classList.toggle('show');
        if (target.classList.contains('show')) {
            target.style.display = 'block';
            const input = target.querySelector('.guest-name-input');
            if (input) input.required = true;
        } else {
            target.style.display = 'none';
            const input = target.querySelector('.guest-name-input');
            if (input) input.required = false;
        }
    });
});
</script>
@endpush
@endif



