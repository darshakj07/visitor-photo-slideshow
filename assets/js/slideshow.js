/**
 * Smart TV / Fullscreen Public Slideshow Engine
 * Smooth crossfades, keyboard accessibility, polling updates, HUD controls
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

'use strict';

class SlideshowApp {
    constructor() {
        this.photos = [];
        this.currentIndex = 0;
        this.intervalSeconds = 8;
        this.timer = null;
        this.isPlaying = true;
        this.pollIntervalMs = 25000; // Poll for new uploads every 25s

        // DOM Elements
        this.viewport = document.getElementById('slideshowViewport');
        this.backdrop = document.getElementById('slideBackdrop');
        this.foreground = document.getElementById('slideImage');
        this.progressBar = document.getElementById('slideshowProgressBar');
        this.visitorBadge = document.getElementById('hudVisitorBadge');
        this.slideCounter = document.getElementById('hudSlideCounter');
        this.emptyState = document.getElementById('slideshowEmptyState');
        this.playPauseBtn = document.getElementById('hudPlayPause');
        this.prevBtn = document.getElementById('hudPrev');
        this.nextBtn = document.getElementById('hudNext');
        this.fullscreenBtn = document.getElementById('hudFullscreen');

        this.init();
    }

    async init() {
        this.bindEvents();
        await this.fetchPhotos();

        if (this.photos.length > 0) {
            this.showSlide(0);
            this.startTimer();
        } else {
            this.showEmptyState();
        }

        // Start background polling for real-time new uploads
        setInterval(() => this.fetchPhotos(true), this.pollIntervalMs);
    }

    async fetchPhotos(isBackgroundPoll = false) {
        try {
            const res = await fetch('../api/slideshow.php', { cache: 'no-store' });
            if (!res.ok) throw new Error('Network error fetching slideshow stream');

            const data = await res.json();
            if (data.success) {
                if (data.interval) {
                    this.intervalSeconds = data.interval;
                }

                const newPhotos = data.photos || [];

                if (!isBackgroundPoll) {
                    this.photos = newPhotos;
                } else {
                    const wasEmpty = this.photos.length === 0;
                    const prevCount = this.photos.length;
                    const activePhotoId = this.photos[this.currentIndex]?.id;

                    this.photos = newPhotos;

                    if (this.photos.length === 0) {
                        this.currentIndex = 0;
                        this.showEmptyState();
                    } else if (wasEmpty) {
                        // Transition from empty queue to populated queue
                        this.hideEmptyState();
                        this.showSlide(0);
                        if (this.isPlaying) {
                            this.startTimer();
                        }
                    } else {
                        // Queue was already populated and remains populated
                        this.hideEmptyState();
                        const newIdx = this.photos.findIndex(p => p.id === activePhotoId);

                        if (newIdx !== -1) {
                            // Active slide still exists; preserve position and update HUD counter
                            this.currentIndex = newIdx;
                            if (this.slideCounter) {
                                this.slideCounter.innerText = `${this.currentIndex + 1} / ${this.photos.length}`;
                            }
                            // If we transitioned from 1 photo to >1 photo and rotation wasn't running, start it
                            if (prevCount <= 1 && this.photos.length > 1 && this.isPlaying && !this.timer) {
                                this.startTimer();
                            }
                        } else {
                            // Active slide was removed/unapproved; gracefully show slide at current boundary
                            const fallbackIdx = (this.currentIndex < this.photos.length) ? this.currentIndex : 0;
                            this.showSlide(fallbackIdx);
                            if (this.isPlaying) {
                                this.startTimer();
                            }
                        }
                    }
                }
            }
        } catch (err) {
            console.warn('Slideshow polling exception:', err);
        }
    }

    showSlide(index) {
        if (this.photos.length === 0) {
            this.showEmptyState();
            return;
        }

        this.hideEmptyState();

        // Wrap index within boundaries
        if (index < 0) {
            index = this.photos.length - 1;
        } else if (index >= this.photos.length) {
            index = 0;
        }

        this.currentIndex = index;
        const currentPhoto = this.photos[this.currentIndex];

        // Smooth crossfade out
        this.foreground.classList.remove('active');

        // Preload next image to ensure seamless display
        const img = new Image();
        img.src = currentPhoto.url;
        img.onload = () => {
            this.foreground.src = currentPhoto.url;
            this.backdrop.style.backgroundImage = `url("${currentPhoto.url}")`;
            this.foreground.classList.add('active');
        };
        img.onerror = () => {
            console.error('Failed to load image:', currentPhoto.url);
            // Auto advance past broken image
            setTimeout(() => this.nextSlide(), 1000);
        };

        // Update HUD labels
        if (this.visitorBadge) {
            if (currentPhoto.caption && currentPhoto.caption.trim() !== '') {
                this.visitorBadge.innerHTML = `<i class="fa-solid fa-camera me-1"></i> ${currentPhoto.caption}`;
                this.visitorBadge.classList.remove('d-none');
            } else {
                this.visitorBadge.classList.add('d-none');
            }
        }

        if (this.slideCounter) {
            this.slideCounter.innerText = `${this.currentIndex + 1} / ${this.photos.length}`;
        }

        this.resetProgressBar();
    }

    startTimer() {
        this.stopTimer();
        if (!this.isPlaying || this.photos.length <= 1) return;

        this.triggerProgressBarAnimation();
        this.timer = setTimeout(() => {
            this.nextSlide();
        }, this.intervalSeconds * 1000);
    }

    stopTimer() {
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
        this.resetProgressBar();
    }

    nextSlide() {
        this.showSlide(this.currentIndex + 1);
        this.startTimer();
    }

    prevSlide() {
        this.showSlide(this.currentIndex - 1);
        this.startTimer();
    }

    togglePlayPause() {
        this.isPlaying = !this.isPlaying;
        if (this.isPlaying) {
            this.playPauseBtn.innerHTML = '<i class="fa-solid fa-pause"></i>';
            this.playPauseBtn.title = 'Pause (Space)';
            this.startTimer();
        } else {
            this.playPauseBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
            this.playPauseBtn.title = 'Play (Space)';
            this.stopTimer();
        }
    }

    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    triggerProgressBarAnimation() {
        if (!this.progressBar) return;
        this.progressBar.style.transition = 'none';
        this.progressBar.style.width = '0%';
        setTimeout(() => {
            this.progressBar.style.transition = `width ${this.intervalSeconds}s linear`;
            this.progressBar.style.width = '100%';
        }, 50);
    }

    resetProgressBar() {
        if (!this.progressBar) return;
        this.progressBar.style.transition = 'none';
        this.progressBar.style.width = '0%';
    }

    showEmptyState() {
        if (this.emptyState) this.emptyState.classList.remove('d-none');
        if (this.foreground) this.foreground.classList.remove('active');
        if (this.backdrop) this.backdrop.style.backgroundImage = 'none';
        if (this.slideCounter) this.slideCounter.innerText = '0 / 0';
        if (this.visitorBadge) this.visitorBadge.classList.add('d-none');
        this.stopTimer();
    }

    hideEmptyState() {
        if (this.emptyState) this.emptyState.classList.add('d-none');
    }

    bindEvents() {
        // Controls
        if (this.playPauseBtn) this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.nextSlide());
        if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.prevSlide());
        if (this.fullscreenBtn) this.fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());

        // Keyboard Navigation (Smart TV / Presentation remote friendly)
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                this.togglePlayPause();
            } else if (e.code === 'ArrowRight' || e.code === 'KeyN') {
                this.nextSlide();
            } else if (e.code === 'ArrowLeft' || e.code === 'KeyP') {
                this.prevSlide();
            } else if (e.code === 'KeyF') {
                this.toggleFullscreen();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SlideshowApp();
});
