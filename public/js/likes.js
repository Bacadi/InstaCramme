document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.like-btn');
    if (!btn || btn.dataset.loading) return;

    btn.dataset.loading = '1'; // évite le double clic pendant la requête

    try {
        const response = await fetch('like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ photo_id: btn.dataset.photoId }),
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        const data = await response.json();;

        
        btn.querySelector('.like-count').textContent = data.likes;
        btn.querySelector('.like-icon').textContent = data.liked ? '❤️' : '🤍';
        btn.classList.toggle('liked', data.liked);
        btn.setAttribute('aria-pressed', data.liked ? 'true' : 'false');

        const likeLabel = btn.querySelector('.like-label');
        if (likeLabel) {
            const label = Number(data.likes) === 1 ? 'like' : 'likes';
            likeLabel.textContent = label;
            btn.setAttribute('aria-label', `${data.likes} ${label}`);
        }
    } catch (err) {
        console.error(err);
    } finally {
        delete btn.dataset.loading;
    }
});