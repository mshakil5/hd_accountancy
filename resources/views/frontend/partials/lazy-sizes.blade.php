<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoThumbnails = document.querySelectorAll('.video-thumbnail');
        const videoModal = new bootstrap.Modal(document.getElementById('videoModal'));
        const videoPlayer = document.getElementById('modalVideoPlayer');
        const videoSource = videoPlayer.querySelector('source');

        videoThumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const videoSrc = this.getAttribute('data-video-src');

                videoSource.setAttribute('src', videoSrc);
                videoPlayer.load();
                videoModal.show();
                videoPlayer.play();
            });
        });
        document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
            videoPlayer.pause();
        });
    });
</script>