<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoThumbnails = document.querySelectorAll('.video-thumbnail');

        videoThumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const videoSrc = this.getAttribute('data-video-src');
                const videoContainer = this.parentElement;

                const videoElement = document.createElement('video');
                videoElement.setAttribute('controls', 'controls');
                videoElement.setAttribute('width', '320');
                videoElement.setAttribute('height', '240');
                videoElement.setAttribute('class', 'rounded-4');

                const sourceElement = document.createElement('source');
                sourceElement.setAttribute('src', videoSrc);
                sourceElement.setAttribute('type', 'video/mp4');

                videoElement.appendChild(sourceElement);

                videoContainer.innerHTML = '';
                videoContainer.appendChild(videoElement);

                videoElement.play();
                $('.testimonial').slick('append', videoContainer);
            });
        });
    });
</script>