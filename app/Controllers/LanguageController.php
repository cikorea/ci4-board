<?php

namespace App\Controllers;

class LanguageController extends BaseController
{
    public function switchLocale(string $locale): \CodeIgniter\HTTP\RedirectResponse
    {
        $supported = ['ko', 'en'];
        if (in_array($locale, $supported, true)) {
            session()->set('locale', $locale);
        }

        $referer = $this->request->getServer('HTTP_REFERER') ?? '/';
        return redirect()->to($referer);
    }
}
