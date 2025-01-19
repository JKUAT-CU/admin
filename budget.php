"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Plus, Trash2, Download } from "lucide-react"
import { useRouter } from "next/navigation"
import { useToast } from "@/components/ui/use-toast"
import { useAuth } from "@/contexts/AuthContext"
import { jsPDF } from "jspdf"
import "jspdf-autotable"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"

interface BudgetItem {
  id: string
  name: string
  quantity: number
  price: number
}

interface Event {
  id: string
  name: string
  attendance: number
  items: BudgetItem[]
}

export function BudgetForm({ semester }: { semester: string }) {
  const [events, setEvents] = useState<Event[]>([])
  const [assets, setAssets] = useState<BudgetItem[]>([])
  const [budgetExists, setBudgetExists] = useState(false)
  const router = useRouter()
  const { toast } = useToast()
  const { currentAccount } = useAuth()
  const [showSuccessAlert, setShowSuccessAlert] = useState(false)

  useEffect(() => {
    checkBudgetExists()
  }, [semester, currentAccount])

  const checkBudgetExists = async () => {
    if (!currentAccount) return

    try {
      const response = await fetch("https://admin.jkuatcu.org/api", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "check-budget-exists",
          department_id: currentAccount.department_id,
          semester,
        }),
        credentials: "include",
      })

      const data = await response.json()
      setBudgetExists(data.exists)
    } catch (error) {
      console.error("Error checking budget existence:", error)
    }
  }

  const addEvent = () => {
    setEvents([...events, { id: Date.now().toString(), name: "", attendance: 0, items: [] }])
  }

  const addEventItem = (eventId: string) => {
    setEvents(
      events.map((event) =>
        event.id === eventId
          ? { ...event, items: [...event.items, { id: Date.now().toString(), name: "", quantity: 0, price: 0 }] }
          : event,
      ),
    )
  }

  const addAsset = () => {
    setAssets([...assets, { id: Date.now().toString(), name: "", quantity: 0, price: 0 }])
  }

  const updateEvent = (eventId: string, field: string, value: string | number) => {
    setEvents(events.map((event) => (event.id === eventId ? { ...event, [field]: value } : event)))
  }

  const updateEventItem = (eventId: string, itemId: string, field: string, value: string | number) => {
    setEvents(
      events.map((event) =>
        event.id === eventId
          ? { ...event, items: event.items.map((item) => (item.id === itemId ? { ...item, [field]: value } : item)) }
          : event,
      ),
    )
  }

  const updateAsset = (assetId: string, field: string, value: string | number) => {
    setAssets(assets.map((asset) => (asset.id === assetId ? { ...asset, [field]: value } : asset)))
  }

  const removeEvent = (eventId: string) => {
    setEvents(events.filter((event) => event.id !== eventId))
  }

  const removeEventItem = (eventId: string, itemId: string) => {
    setEvents(
      events.map((event) =>
        event.id === eventId ? { ...event, items: event.items.filter((item) => item.id !== itemId) } : event,
      ),
    )
  }

  const removeAsset = (assetId: string) => {
    setAssets(assets.filter((asset) => asset.id !== assetId))
  }

  const calculateTotal = (items: BudgetItem[]) => {
    return items.reduce((total, item) => total + item.quantity * item.price, 0)
  }

  const calculateGrandTotal = () => {
    const eventsTotal = events.reduce((total, event) => total + calculateTotal(event.items), 0)
    const assetsTotal = calculateTotal(assets)
    return eventsTotal + assetsTotal
  }

  const validateBudget = () => {
    if (events.length === 0 && assets.length === 0) {
      toast({
        title: "Validation Error",
        description: "Please add at least one event or asset to the budget.",
        variant: "destructive",
      })
      return false
    }

    for (const event of events) {
      if (!event.name || event.attendance <= 0 || event.items.length === 0) {
        toast({
          title: "Validation Error",
          description: "Please fill in all event details and add at least one item to each event.",
          variant: "destructive",
        })
        return false
      }
      for (const item of event.items) {
        if (!item.name || item.quantity <= 0 || item.price <= 0) {
          toast({
            title: "Validation Error",
            description: "Please fill in all item details for each event.",
            variant: "destructive",
          })
          return false
        }
      }
    }

    for (const asset of assets) {
      if (!asset.name || asset.quantity <= 0 || asset.price <= 0) {
        toast({
          title: "Validation Error",
          description: "Please fill in all asset details.",
          variant: "destructive",
        })
        return false
      }
    }

    if (calculateGrandTotal() <= 0) {
      toast({
        title: "Validation Error",
        description: "The total budget amount must be greater than zero.",
        variant: "destructive",
      })
      return false
    }

    return true
  }

  const generatePDF = () => {
    const doc = new jsPDF()
    doc.setFontSize(18)
    doc.text(`Budget for ${currentAccount?.department_name} - ${semester}`, 14, 22)
    doc.setFontSize(12)
    doc.text(`Date: ${new Date().toLocaleDateString()}`, 14, 32)

    let yPos = 40

    // Events
    events.forEach((event, index) => {
      doc.setFontSize(14)
      doc.text(`Event: ${event.name}`, 14, yPos)
      yPos += 10
      doc.setFontSize(12)
      doc.text(`Attendees: ${event.attendance}`, 14, yPos)
      yPos += 10

      const eventItemsData = event.items.map((item) => [
        item.name,
        item.quantity.toString(),
        `$${item.price.toFixed(2)}`,
        `$${(item.quantity * item.price).toFixed(2)}`,
      ])

      doc.autoTable({
        startY: yPos,
        head: [["Item", "Quantity", "Price", "Total"]],
        body: eventItemsData,
      })

      yPos = (doc as any).lastAutoTable.finalY + 10
      doc.text(`Event Total: $${calculateTotal(event.items).toFixed(2)}`, 14, yPos)
      yPos += 15
    })

    // Assets
    if (assets.length > 0) {
      doc.setFontSize(14)
      doc.text("Assets", 14, yPos)
      yPos += 10

      const assetsData = assets.map((asset) => [
        asset.name,
        asset.quantity.toString(),
        `$${asset.price.toFixed(2)}`,
        `$${(asset.quantity * asset.price).toFixed(2)}`,
      ])

      doc.autoTable({
        startY: yPos,
        head: [["Asset", "Quantity", "Price", "Total"]],
        body: assetsData,
      })

      yPos = (doc as any).lastAutoTable.finalY + 10
    }

    // Grand Total
    doc.setFontSize(16)
    doc.text(`Grand Total: $${calculateGrandTotal().toFixed(2)}`, 14, yPos)

    return doc
  }

  const handleSubmit = async () => {
    if (!currentAccount) {
      toast({
        title: "Error",
        description: "No account selected. Please select an account first.",
        variant: "destructive",
      })
      return
    }

    if (budgetExists) {
      toast({
        title: "Budget Already Exists",
        description:
          "A budget for this department and semester already exists. Please edit the existing budget if changes are needed.",
        variant: "destructive",
      })
      return
    }

    if (!validateBudget()) {
      return
    }

    const payload = {
      action: "submit-budget",
      semester,
      department_id: currentAccount.department_id,
      events: events.map((event) => ({
        name: event.name,
        attendance: event.attendance,
        items: event.items.map((item) => ({
          name: item.name,
          quantity: item.quantity,
          price: item.price,
          total: item.quantity * item.price,
        })),
        total: calculateTotal(event.items),
      })),
      assets: assets.map((asset) => ({
        name: asset.name,
        quantity: asset.quantity,
        price: asset.price,
        total: asset.quantity * asset.price,
      })),
      grandTotal: calculateGrandTotal(),
    }

    try {
      const response = await fetch("https://admin.jkuatcu.org/api", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
        credentials: "include",
      })

      const data = await response.json()

      if (response.ok) {
        // Generate PDF
        const pdf = generatePDF()
        pdf.save(`budget_${currentAccount.department_name}_${semester}.pdf`)

        // Send PDF to backend
        const pdfBlob = pdf.output("blob")
        const formData = new FormData()
        formData.append("pdf", pdfBlob, `budget_${currentAccount.department_name}_${semester}.pdf`)
        formData.append("action", "upload-pdf")
        formData.append("department_id", currentAccount.department_id.toString())
        formData.append("semester", semester)

        const uploadResponse = await fetch("https://admin.jkuatcu.org/api", {
          method: "POST",
          body: formData,
          credentials: "include",
        })

        if (!uploadResponse.ok) {
          console.error("Failed to upload PDF to server")
        }

        setShowSuccessAlert(true)
      } else {
        toast({
          title: "Submission Error",
          description: data.message || "Failed to submit budget. Please try again.",
          variant: "destructive",
        })
      }
    } catch (error) {
      console.error("Error submitting budget:", error)
      toast({
        title: "Submission Error",
        description: "An unexpected error occurred. Please try again.",
        variant: "destructive",
      })
    }
  }

  return (
    <>
      <div className="space-y-8">
        <Card>
          <CardHeader>
            <CardTitle className="flex justify-between items-center">
              <span>Event Budget - {semester}</span>
              {budgetExists && <span className="text-yellow-500">Budget already exists for this semester</span>}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {events.map((event) => (
              <div key={event.id} className="mb-8 border p-4 rounded-lg">
                <div className="flex gap-4 mb-4">
                  <Input
                    placeholder="Event Name"
                    value={event.name}
                    onChange={(e) => updateEvent(event.id, "name", e.target.value)}
                  />
                  <Input
                    type="number"
                    placeholder="Attendance"
                    value={event.attendance}
                    onChange={(e) => updateEvent(event.id, "attendance", Number.parseInt(e.target.value))}
                  />
                  <Button variant="destructive" size="icon" onClick={() => removeEvent(event.id)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Item</TableHead>
                      <TableHead>Price per item</TableHead>
                      <TableHead>Quantity</TableHead>
                      <TableHead>Total</TableHead>
                      <TableHead></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {event.items.map((item) => (
                      <TableRow key={item.id}>
                        <TableCell>
                          <Input
                            placeholder="Item Name"
                            value={item.name}
                            onChange={(e) => updateEventItem(event.id, item.id, "name", e.target.value)}
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            placeholder="Price"
                            value={item.price}
                            onChange={(e) =>
                              updateEventItem(event.id, item.id, "price", Number.parseFloat(e.target.value))
                            }
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            placeholder="Quantity"
                            value={item.quantity}
                            onChange={(e) =>
                              updateEventItem(event.id, item.id, "quantity", Number.parseInt(e.target.value))
                            }
                          />
                        </TableCell>
                        <TableCell>{(item.price * item.quantity).toFixed(2)}</TableCell>
                        <TableCell>
                          <Button variant="destructive" size="icon" onClick={() => removeEventItem(event.id, item.id)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
                <Button onClick={() => addEventItem(event.id)} className="mt-4">
                  <Plus className="mr-2 h-4 w-4" /> Add Item
                </Button>
                <div className="text-right mt-4">
                  <strong>Event Total: {calculateTotal(event.items).toFixed(2)}</strong>
                </div>
              </div>
            ))}
            <Button onClick={addEvent}>
              <Plus className="mr-2 h-4 w-4" /> Add Event
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Asset Budget - {semester}</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Item</TableHead>
                  <TableHead>Price per item</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {assets.map((asset) => (
                  <TableRow key={asset.id}>
                    <TableCell>
                      <Input
                        placeholder="Item Name"
                        value={asset.name}
                        onChange={(e) => updateAsset(asset.id, "name", e.target.value)}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        placeholder="Price"
                        value={asset.price}
                        onChange={(e) => updateAsset(asset.id, "price", Number.parseFloat(e.target.value))}
                      />
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        placeholder="Quantity"
                        value={asset.quantity}
                        onChange={(e) => updateAsset(asset.id, "quantity", Number.parseInt(e.target.value))}
                      />
                    </TableCell>
                    <TableCell>{(asset.price * asset.quantity).toFixed(2)}</TableCell>
                    <TableCell>
                      <Button variant="destructive" size="icon" onClick={() => removeAsset(asset.id)}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <Button onClick={addAsset} className="mt-4">
              <Plus className="mr-2 h-4 w-4" /> Add Asset
            </Button>
            <div className="text-right mt-4">
              <strong>Assets Total: {calculateTotal(assets).toFixed(2)}</strong>
            </div>
          </CardContent>
        </Card>

        <div className="text-right">
          <h3 className="text-xl font-bold">Grand Total: {calculateGrandTotal().toFixed(2)}</h3>
        </div>

        <Button onClick={handleSubmit} className="w-full" disabled={budgetExists}>
          {budgetExists ? "Budget Already Exists" : "Submit Budget"}
        </Button>
      </div>

      <AlertDialog open={showSuccessAlert} onOpenChange={setShowSuccessAlert}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Budget Submitted Successfully</AlertDialogTitle>
            <AlertDialogDescription>
              Your budget has been successfully submitted for {currentAccount?.department_name}. A PDF of your budget
              has been generated and uploaded to the server.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogAction onClick={() => router.push("/dashboard")}>Return to Dashboard</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}

