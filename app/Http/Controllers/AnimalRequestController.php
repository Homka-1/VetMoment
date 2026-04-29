<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnimalRequest;
use Illuminate\Support\Facades\Auth;

use App\Notifications\RequestAccepted;

class AnimalRequestController extends Controller
{
    public function index()
    {
        $requests = AnimalRequest::with(['user', 'helper'])
            ->whereIn('status', ['active', 'in_progress'])
            ->latest()
            ->get();
            
        return view('home', compact('requests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        AnimalRequest::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('home')->with('success', 'Запрос успешно опубликован!');
    }

    public function accept(AnimalRequest $animalRequest)
    {
        if ($animalRequest->status !== 'active') {
            return back()->withErrors('Этот запрос уже принят в работу.');
        }

        $animalRequest->update([
            'status' => 'in_progress',
            'helper_id' => Auth::id(),
        ]);

        $animalRequest->user->notify(new RequestAccepted(Auth::user(), $animalRequest));

        return redirect()->route('home')->with('success', 'Спасибо за отклик! Автор уведомлен и свяжется с вами.');
    }

    public function close(AnimalRequest $animalRequest)
    {
        if ($animalRequest->user_id !== Auth::id()) {
            return back()->withErrors('Только автор может закрыть заявку.');
        }

        $animalRequest->update(['status' => 'resolved']);
        return redirect()->route('home')->with('success', 'Заявка успешно закрыта!');
    }
}
