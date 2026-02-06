<?php

namespace Modules\CimsSignature\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\CimsSignature\Models\ClientSignature;

class SignatureController extends Controller
{
    /**
     * Display list of captured signatures
     */
    public function index(Request $request)
    {
        $query = ClientSignature::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_reference', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        $signatures = $query->orderBy('captured_at', 'desc')->paginate(15);

        return view('cims_signature::signatures.index', compact('signatures'));
    }

    /**
     * Show signature capture form
     */
    public function create()
    {
        return view('cims_signature::signatures.capture');
    }

    /**
     * Store captured signature
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_reference' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:50',
            'signature_data' => 'required|string', // Base64 PNG
            'purpose' => 'nullable|string|max:255',
            'document_reference' => 'nullable|string|max:100',
        ]);

        // Generate hash for integrity verification
        $signatureHash = hash('sha256', $validated['signature_data']);

        $signature = ClientSignature::create([
            'client_name' => $validated['client_name'],
            'client_reference' => $validated['client_reference'],
            'id_number' => $validated['id_number'],
            'signature_data' => $validated['signature_data'],
            'signature_hash' => $signatureHash,
            'purpose' => $validated['purpose'],
            'document_reference' => $validated['document_reference'],
            'ip_address' => $request->ip(),
            'device_info' => 'Wacom STU-430',
            'captured_by' => Auth::id(),
            'captured_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signature captured successfully',
            'signature_id' => $signature->id,
        ]);
    }

    /**
     * Display a specific signature
     */
    public function show(ClientSignature $signature)
    {
        return view('cims_signature::signatures.show', compact('signature'));
    }

    /**
     * Delete a signature
     */
    public function destroy(ClientSignature $signature): JsonResponse
    {
        $signature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signature deleted successfully',
        ]);
    }

    /**
     * Get signature image for embedding
     */
    public function getImage(ClientSignature $signature): JsonResponse
    {
        return response()->json([
            'success' => true,
            'signature_data' => $signature->signature_data,
            'client_name' => $signature->client_name,
            'captured_at' => $signature->captured_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get latest signature for a client reference
     */
    public function getLatest(string $clientReference): JsonResponse
    {
        $signature = ClientSignature::getLatestForClient($clientReference);

        if (!$signature) {
            return response()->json([
                'success' => false,
                'message' => 'No signature found for this client',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'signature_id' => $signature->id,
            'signature_data' => $signature->signature_data,
            'client_name' => $signature->client_name,
            'captured_at' => $signature->captured_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Quick capture page - standalone signature capture
     */
    public function quickCapture()
    {
        return view('cims_signature::signatures.quick');
    }
}
