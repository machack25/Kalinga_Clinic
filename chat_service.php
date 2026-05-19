<?php
session_start();
include("db.php");
header('Content-Type: application/json');


$apiKey = getenv('GEMINI_API_KEY');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'Sorry, I can only help logged-in users.']);
    exit();
}

$patient_id = $_SESSION['user_id'];
$user_message = $_POST['message'] ?? '';

if (empty($user_message)) {
    echo json_encode(['reply' => 'Please type a message.']);
    exit();
}


$bot_response = generate_response($user_message, $con, $patient_id, $apiKey);


try {
    $stmt = $con->prepare("INSERT INTO chat_logs (patient_id, user_message, bot_response) VALUES (?, ?, ?)");
    
    $stmt->bind_param("iss", $patient_id, $user_message, $bot_response);
    $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    
    error_log("Failed to log chat message: " . $e->getMessage());
}

echo json_encode(['reply' => $bot_response]);
$con->close();


function generate_response($message, $db_connection, $patient_id, $apiKey = "") {
    
    if (!empty($apiKey)) {
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

        
        $systemInstruction = "You have ONLY ONE role: **General Health Information Source.**

        Your *sole purpose* is to answer general knowledge and health-related questions (e.g., 'What are the symptoms of a UTI?', 'How does paracetamol work?'). You must draw from general, open-source medical knowledge.

        **CRITICAL SAFETY PROTOCOL: THE MEDICAL DISCLAIMER**
        When a user asks a general health question, you MUST provide the information, but you MUST ALWAYS end your response with the following disclaimer, separated by a line:
        ---
        *This information is for educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or another qualified health provider with any questions you may have regarding a medical condition.*

        **ABSOLUTE RULE 1: NO DIAGNOSIS/PERSONAL ADVICE.** NEVER diagnose a user or give personalized medical advice, even if they describe their own symptoms. If a user asks for a diagnosis (e.g., 'I have a fever, what should I do?'), you must refuse and guide them to consult a doctor, using a phrase like, 'I cannot provide medical advice or diagnosis. It's best to consult with a doctor for your symptoms.'

        **ABSOLUTE RULE 2: REFUSE NON-MEDICAL QUESTIONS.** You MUST refuse to answer ANY question that is NOT a general health or medical information query. This includes questions about clinic appointments, location, hours, website navigation, greetings (hello, thank you), or any other off-topic subject. Respond politely but firmly, stating your limited function, for example: 'I can only answer general health and medical information questions.' or 'Sorry, I cannot help with that request. My function is limited to providing general medical information.'";
        

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $message]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ]
        ];

        $headers = ["Content-Type: application/json"];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Increased timeout

        $api_result = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("cURL Error accessing Gemini API: " . $curl_error);
            
        } else {
            $response_data = json_decode($api_result, true);
            
            if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                $raw_text = $response_data['candidates'][0]['content']['parts'][0]['text'];
                
                return nl2br(htmlspecialchars($raw_text));
            } elseif (isset($response_data['error'])) {
                 error_log("Gemini API Error: " . json_encode($response_data['error']));
                 
            } else {
                 error_log("Unexpected Gemini API response format: " . $api_result);
                 
            }
        }
    } else {
         error_log("Gemini API Key is not configured.");
         
    }

     
    return "Sorry, I am currently experiencing technical difficulties or cannot fulfill that request. I can only provide general health and medical information.";
}
?>