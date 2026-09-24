<?php

namespace App\Livewire\Documents;

use App\Jobs\ProcessQualityDocumentJob;
use App\Models\QualityDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DocumentList extends Component
{
    use WithFileUploads;

    public $file = null;
    public string $name = '';
    public string $document_type = 'quality_manual';

    public function mount(): void
    {
        abort_if(auth()->user()->isSupplier() || auth()->user()->isReadOnly(), 403);
    }

    public function upload(): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);

        $data = $this->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:'.implode(',', array_keys(\App\Models\QualityDocument::TYPE_LABELS))],
        ]);

        $path = $this->file->store('quality-documents', 'local');

        $document = QualityDocument::create([
            'name' => $data['name'],
            'file_path' => $path,
            'original_filename' => $this->file->getClientOriginalName(),
            'document_type' => $data['document_type'],
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        ProcessQualityDocumentJob::dispatch($document->id);

        $this->reset(['file', 'name']);
        $this->document_type = 'quality_manual';

        session()->flash('status', "\"{$document->name}\" uploaded. It will appear in the AI Assistant once processing finishes.");
    }

    public function delete(int $documentId): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);

        $document = QualityDocument::findOrFail($documentId);
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
    }

    public function retry(int $documentId): void
    {
        abort_unless(auth()->user()->canManageNcrs(), 403);

        $document = QualityDocument::findOrFail($documentId);
        ProcessQualityDocumentJob::dispatch($document->id);

        session()->flash('status', "Retrying \"{$document->name}\".");
    }

    public function render()
    {
        return view('livewire.documents.list', [
            'documents' => QualityDocument::with('createdBy')->latest()->get(),
        ]);
    }
}
