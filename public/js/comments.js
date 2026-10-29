document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.comment-form');
    if (!form) return;

    event.preventDefault();
    if (form.dataset.loading) return;

    const input = form.querySelector('[name="content"]');
    const feedback = form.querySelector('.comment-feedback');
    const content = input.value.trim();
    if (!content) {
        feedback.textContent = 'Écris un commentaire avant de publier.';
        return;
    }

    form.dataset.loading = '1';
    form.querySelector('button[type="submit"]').disabled = true;
    feedback.textContent = '';

    try {
        const response = await fetch('comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                photo_id: form.dataset.photoId,
                content,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}`);
        }

        const list = form.parentElement.querySelector('.post-comments');
        const item = document.createElement('li');
        item.className = 'post-comment';

        const avatarLink = document.createElement('a');
        avatarLink.className = 'avatar-link';
        avatarLink.href = `profile.php?id=${encodeURIComponent(data.author_id)}`;

        const avatar = document.createElement('img');
        avatar.className = 'user-avatar';
        avatar.src = data.author_photo_path || '../tools/assets/icon.svg';
        avatar.alt = '';
        avatarLink.append(avatar);

        const author = document.createElement('a');
        author.className = 'comment-author';
        author.href = `profile.php?id=${encodeURIComponent(data.author_id)}`;
        author.textContent = data.username;

        const commentText = document.createElement('span');
        commentText.className = 'comment-content';
        commentText.textContent = data.content;

        item.append(avatarLink, author, document.createTextNode(' : '), commentText);
        list.append(item);
        input.value = '';
        feedback.textContent = 'Commentaire publié.';
    } catch (error) {
        console.error(error);
        feedback.textContent = error.message || 'Impossible de publier le commentaire.';
    } finally {
        delete form.dataset.loading;
        form.querySelector('button[type="submit"]').disabled = false;
    }
});
