<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoModalElement = document.getElementById('videoModal');
    if (videoModalElement) {
        const videoThumbnails = document.querySelectorAll('.video-thumbnail');
        const videoModal = new bootstrap.Modal(videoModalElement);
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
        videoModalElement.addEventListener('hidden.bs.modal', function () {
            videoPlayer.pause();
        });
    }
});
</script>