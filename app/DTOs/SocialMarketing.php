<?php

namespace App\DTOs;

readonly class SocialMarketing
{
    public function __construct(
        public string $socialPost,
        public string $emailSubject,
        public string $emailIntro,
    ) {}

    public function toArray(): array
    {
        return [
            'social_post' => $this->socialPost,
            'email_subject' => $this->emailSubject,
            'email_intro' => $this->emailIntro,
        ];
    }
}
