<div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-comments"></i> Comentarios</h5>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <h6 class="mb-3">Deja tu comentario</h6>
                <form wire:submit.prevent="submitComment">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" wire:model.defer="name" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Email (opcional)</label>
                        <input type="email" class="form-control" wire:model.defer="email">
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-group">
                        <label>Comentario</label>
                        <textarea class="form-control" rows="3" wire:model.defer="commentText" required></textarea>
                        @error('commentText') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
                </form>
            </div>

            @forelse($comments as $comment)
                <div class="border-bottom pb-3 mb-3" wire:key="comment-{{ $comment->id }}">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $comment->name }}</strong>
                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-2">{{ $comment->comment }}</p>
                    <div class="d-flex align-items-center flex-wrap mb-2">
                        @php
                            $counts = [
                                'like' => $comment->reactions->where('type', 'like')->count(),
                                'sad' => $comment->reactions->where('type', 'sad')->count(),
                                'laugh' => $comment->reactions->where('type', 'laugh')->count(),
                                'angry' => $comment->reactions->where('type', 'angry')->count(),
                            ];
                        @endphp
                        <button class="btn btn-sm btn-outline-primary mr-2" wire:click="react({{ $comment->id }}, 'like')">
                            <i class="far fa-thumbs-up"></i> {{ $counts['like'] }}
                        </button>
                        <button class="btn btn-sm btn-outline-secondary mr-2" wire:click="react({{ $comment->id }}, 'sad')">
                            <i class="far fa-face-sad-tear"></i> {{ $counts['sad'] }}
                        </button>
                        <button class="btn btn-sm btn-outline-success mr-2" wire:click="react({{ $comment->id }}, 'laugh')">
                            <i class="far fa-face-laugh"></i> {{ $counts['laugh'] }}
                        </button>
                        <button class="btn btn-sm btn-outline-danger mr-2" wire:click="react({{ $comment->id }}, 'angry')">
                            <i class="far fa-face-angry"></i> {{ $counts['angry'] }}
                        </button>
                        <button class="btn btn-sm btn-link" wire:click="startReply({{ $comment->id }})">
                            Responder
                        </button>
                    </div>

                    @if($comment->email)
                        <small class="text-muted d-block mb-2"><i class="fas fa-envelope"></i> {{ $comment->email }}</small>
                    @endif

                    @if($replyingTo === $comment->id)
                        <div class="mt-2">
                            <form wire:submit.prevent="submitReply">
                                <div class="form-group">
                                    <label>Nombre</label>
                                    <input type="text" class="form-control" wire:model.defer="replyName" required>
                                    @error('replyName') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <div class="form-group">
                                    <label>Email (opcional)</label>
                                    <input type="email" class="form-control" wire:model.defer="replyEmail">
                                    @error('replyEmail') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <div class="form-group">
                                    <label>Respuesta</label>
                                    <textarea class="form-control" rows="2" wire:model.defer="replyText" required></textarea>
                                    @error('replyText') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-reply"></i> Responder</button>
                            </form>
                        </div>
                    @endif

                    @if($comment->replies->count())
                        <div class="mt-3 ml-3">
                            @foreach($comment->replies as $reply)
                                <div class="border-left pl-3 mb-2" wire:key="reply-{{ $reply->id }}">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $reply->name }}</strong>
                                        <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">{{ $reply->comment }}</p>
                                    @if($reply->email)
                                        <small class="text-muted"><i class="fas fa-envelope"></i> {{ $reply->email }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">Aún no hay comentarios para este utilitario.</p>
            @endforelse
        </div>
    </div>
</div>
