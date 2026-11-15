<?php

namespace App\Http\Controllers\Admin;

use App\Lib\FormProcessor;
use App\Models\SocialMedia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SocialMediaController extends Controller
{
    public function index()
    {
        $pageTitle    = 'Manage Platforms';
        $socialsMedia = SocialMedia::searchable(['name'])->withCount('accountListing')->orderBy('name')->paginate(getPaginate());
        return view('admin.social_media.index', compact('pageTitle', 'socialsMedia'));
    }

    public function store(Request $request, $id = null)
    {
        $request->validate([
            'name'         => 'required',
            'domain'       => 'required',
            'url'          => 'required|url',
            'instructions' => 'nullable|string',
        ]);

        if ($id) {
            $socialMedia   = SocialMedia::findOrFail($id);
            $notifyMessage = 'Platform updated successfully';
        } else {
            $socialMedia   = new SocialMedia();
            $notifyMessage = 'Platform added successfully';
        }

        $socialMedia->name         = $request->name;
        $socialMedia->domain       = $request->domain;
        $socialMedia->url          = $request->url;
        $socialMedia->instructions = $request->instructions;
        $socialMedia->save();

        $notify[] = ['success', $notifyMessage];
        return back()->withNotify($notify);
    }

    public function info($id)
    {
        $socialMedia = SocialMedia::findOrFail($id);
        $form        = $socialMedia->form;
        $pageTitle   = 'Add Social Media Information';
        return view('admin.social_media.add_info', compact('pageTitle', 'socialMedia', 'form'));
    }

    public function infoStore(Request $request, $id)
    {
        $formProcessor = new FormProcessor();

        $generatorValidation = $formProcessor->generatorValidation();
        $validation          =  $generatorValidation['rules'];
        $request->validate($validation, $generatorValidation['messages']);

        $socialMedia   = SocialMedia::findOrFail($id);
        $generate      = $formProcessor->generate('social_media', true, 'id', $socialMedia->form_id);

        $socialMedia->form_id = $generate->id;
        $socialMedia->save();

        $notify[] = ['success', 'Social media requirements updated successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return SocialMedia::changeStatus($id);
    }

    public function delete($id)
    {
        $socialMedia = SocialMedia::withCount('accountListing')->findOrFail($id);

        // Delete associated accounts
        foreach ($socialMedia->accountListing as $account) {
            $account->delete();
        }

        $socialMedia->delete();

        $notify[] = ['success', 'Platform and all associated accounts deleted successfully'];
        return back()->withNotify($notify);
    }

    public function loadBalance(Request $request, $id)
    {
        $mode = $request->input('mode', 'override_manual');
        if (!in_array($mode, ['override_manual', 'keep_manual'])) {
            $mode = 'override_manual';
        }

        $platform = SocialMedia::findOrFail($id);
        $updatedUsersCount = self::executeLoadBalance($platform->id, $mode);

        $notify[] = ['warning', "Automatic account assignment is disabled for {$platform->name}. Assign accounts directly from each user's admin panel."];
        return back()->withNotify($notify);
    }

    /**
     * Automatic load balancing is intentionally disabled. Account access is
     * now an explicit per-user assignment managed from the admin user form.
     * This method remains as a compatibility no-op for older admin actions
     * and cookie checks that still call it.
     */
    public static function executeLoadBalance($platformId, $mode = 'override_manual')
    {
        return 0;
    }
}
