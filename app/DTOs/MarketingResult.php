<?php

namespace App\DTOs;

readonly class MarketingResult
{
    public function __construct(
        public string $socialPost,
        public string $emailSubject,
        public string $emailIntro,
    ) {}

    /** @return array{social_post: string, email_subject: string, email_intro: string} */
    public function toArray(): array
    {
        return [
            'social_post' => $this->socialPost,
            'email_subject' => $this->emailSubject,
            'email_intro' => $this->emailIntro,
        ];
    }
}
